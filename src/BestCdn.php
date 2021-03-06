<?php

namespace BlackBits\BestCdn;

use BlackBits\BestCdn\Exception\BestCdnException;

use BlackBits\BestCdn\Testing\MockClient;
use BlackBits\BestCdn\Traits\BestCdnHelpers;
use BlackBits\BestCdn\Traits\RunsChecks;
use Psr\Http\Message\UriInterface;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

class BestCdn
{
    use BestCdnHelpers, RunsChecks;

    /*
     * WARNING: circumventing this limit manually may result in time-outs and/or max post size errors
     */
    const UPLOAD_LIMIT = 5 * 1024 * 1024; // 5mb

    /**
     * @var Client|null
     */
    protected $httpClient = null;

    /**
     * @var string
     */
    protected $connection = "default";

    /**
     * BestCdn constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->connection = empty($config['defaultConnection']) ?: "default";
    }

    /**
     * Ensures that a client is present and returns it
     *
     * @return MockClient|Client
     * @throws BestCdnException
     */
    protected function client()
    {
        $this->validateConfig();
        if ($this->config['defaultConnection'] == "mock-connection") {
            return $this->httpClient ?: new MockClient();
        }
        return $this->httpClient ?: new Client($this->config['connections']['default']['defaultRequestOptions']);
    }

    /**
     * Returns the authentication token
     *
     * @return string
     */
    protected function getAuthToken()
    {
        // TODO: refactor to use JWT later?
        return "Bearer " . base64_encode(implode(":", $this->config['connections']['default']['credentials']));
    }

    /**
     * Create and send a guzzle request
     *
     * @param $method string
     * @param $uri string|UriInterface
     * @param array $options
     * @param bool $auth
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    protected function request(string $method, $uri, array $options = [], $auth = true)
    {
        $options['headers'] = array_merge([
            'User-Agent'    => 'BlackBits-BestCDN-SDK-PHP/1.0',
            'Accept'        => 'application/json',
        ], empty($options['headers']) ? [] : $options['headers']);

        if ($auth) {
            $options['headers']['Authorization'] = $this->getAuthToken();
        }

        try {
            $response = $this->client()->request($method, $uri, $options);
        }
        catch (ClientException $e)
        {
            // 4XX Errors
            if ($e->getResponse()->getStatusCode() == 404) {
                $response = $e;
            } else {
                throw new BestCdnException(
                    $e->getResponse()->getReasonPhrase(),
                    $e->getResponse()->getStatusCode(),
                    [
                        'request'  => $e->getRequest(),
                        'response' => $e->getResponse(),
                        'body'     => $e->getResponse()->getBody()->getContents(),
                    ],
                    $e
                );
            }


        }
        catch (ServerException $e)
        {
            // 5XX Errors
            throw new BestCdnException(
                $e->getResponse()->getReasonPhrase(),
                $e->getResponse()->getStatusCode(),
                [
                    'request'  => $e->getRequest(),
                    'response' => $e->getResponse(),
                ],
                $e
            );
        }
        catch (RequestException $e)
        {
            // Something went wrong before the request was sent
            throw new BestCdnException(
                $e->getMessage(),
                $e->getCode(),
                [
                    'request'  => $e->getRequest(),
                    'response' => $e->getResponse(),
                ],
                $e
            );
        }
        return $response = BestCdnResult::create($response);

    }


    /**
     * @param string $key
     * @param string $type
     * @return mixed
     * @throws BestCdnException
     * @throws \Throwable
     */
    protected function getPreSignedURI(string $key, $type = "store")
    {
        $uri = "/api/file/{$type}-s3/get-presigned-url";

        $options = [
            'json' => [
                "file_key" => $key,
            ],
        ];

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        throw_if($response->hasError(), new BestCdnException("Cannot get presigned_url"));

        return $response->get("presigned_url");
    }

    /**
     * @param string $uri
     * @param $resource
     * @param $md5
     * @throws BestCdnException
     */
    protected function uploadToS3(string $uri, $resource, $md5)
    {
        $options = [
            'body' => $resource
        ];

        // do the upload as put
        $response = $this->client()->request('PUT', $uri, $options);

        // fix the etag header
        $etag = trim($response->getHeader("ETag")[0] ?? "","\"");

        if ($response->getStatusCode() != 200 || $etag != $md5) {
            throw new BestCdnException("Upload to S3 failed", 500, [
                "errors" => [
                    "statusCode" => $response->getStatusCode(),
                    "md5"        => $md5,
                    "ETag"       => $etag,
                ],
                "response" => $response,
            ]);
        }

        if (is_resource($resource)){
            fclose($resource);
        }
    }

    /**
     * @param string $key
     * @param string $uri
     * @param resource $resource
     * @param string $md5
     * @return BestCdnResult
     * @throws BestCdnException
     */
    protected function putViaS3(string $key, string $uri, $resource, $md5)
    {
        $this->uploadToS3($uri, $resource, $md5);

        // confirm the finished upload with the CDN
        $uri  = "/api/file/store-s3";
        $options = [
            'json' => [
                "file_key" => $key,
            ],
        ];

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param string $key
     * @param resource $resource
     * @return BestCdnResult
     * @throws BestCdnException
     */
    protected function putBinary(string $key, resource $resource)
    {
        $options = [
            'multipart' => [
                [
                    'name'     => "file_key",
                    'contents' => $key,
                ],
                [
                    'name'     => "file",
                    'contents' => $resource,
                ],
            ],
        ];
        $uri = "/api/file/store-bin";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        if (is_resource($resource)){
            fclose($resource);
        }

        return $response;
    }

    /**
     * @param string $key
     * @param string $uri
     * @param $resource
     * @param $md5
     * @return BestCdnResult
     * @throws BestCdnException
     */
    protected function updateViaS3(string $key, string $uri, $resource, $md5)
    {
        $this->uploadToS3($uri, $resource, $md5);

        // confirm the finished upload with the CDN
        $uri  = "/api/file/update-s3";
        $options = [
            'json' => [
                "file_key" => $key,
            ],
        ];

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param string $key
     * @param resource $resource
     * @return BestCdnResult
     * @throws BestCdnException
     */
    protected function updateBinary(string $key, resource $resource)
    {
        $options = [
            'multipart' => [
                [
                    'name'     => "file_key",
                    'contents' => $key,
                ],
                [
                    'name'     => "file",
                    'contents' => $resource,
                ],
            ],
        ];
        $uri = "/api/file/update-bin";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        if (is_resource($resource)){
            fclose($resource);
        }

        return $response;
    }

    /**
     * @param string $key
     * @param string $uri
     * @param $resource
     * @param $md5
     * @return BestCdnResult
     * @throws BestCdnException
     */
    protected function updateOrCreateViaS3(string $key, string $uri, $resource, $md5)
    {
        $this->uploadToS3($uri, $resource, $md5);

        // confirm the finished upload with the CDN
        $uri  = "/api/file/update-or-create-s3";
        $options = [
            'json' => [
                "file_key" => $key,
            ],
        ];

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param string $key
     * @param resource $resource
     * @return BestCdnResult
     * @throws BestCdnException
     */
    protected function updateOrCreateBinary(string $key, resource $resource)
    {
        $options = [
            'multipart' => [
                [
                    'name'     => "file_key",
                    'contents' => $key,
                ],
                [
                    'name'     => "file",
                    'contents' => $resource,
                ],
            ],
        ];
        $uri = "/api/file/update-or-create-bin";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        if (is_resource($resource)){
            fclose($resource);
        }

        return $response;
    }


    /**
     * Run a request on a specified connection
     *
     * @param string $connection
     *
     * @return $this
     *
     * @throws BestCdnException
     */
    public function on(string $connection)
    {
        $this->validateConnection($connection);
        $this->connection = $connection;
        return $this;
    }

    /*
     * Endpoints
     */

    /**
     * @param string $key
     * @param string $file
     * @return BestCdnResult
     * @throws BestCdnException
     * @throws \Throwable
     */
    public function putFile(string $key, string $file)
    {
        $key = self::sanitizeFilename($key);

        $this
            ->checkKey($key)
            ->checkFile($file)
            ->runChecks();

        $fileHandle = fopen($file, 'r');
        $fileStats  = fstat($fileHandle);

        // upload files bigger than our upload limit to S3 otherwise upload them directly as binary
        if ($fileStats['size'] > self::UPLOAD_LIMIT) {
            return $this->putViaS3($key, $this->getPreSignedURI($key, "store"), $fileHandle, md5_file($file));
        } else {
            return $this->putViaS3($key, $this->getPreSignedURI($key, "store"), $fileHandle, md5_file($file)); // TODO: change this to binary upload later for performance optimization
        }
    }

    /**
     * @param string $key
     * @param string $uri
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function putFileByUri(string $key, string $uri)
    {
        $key = self::sanitizeFilename($key);

        $this
            ->checkKey($key)
            ->checkUri($uri)
            ->runChecks();

        $options = [
            'json' => [
                "file_key" => $key,
                "uri"      => $uri,
            ],
        ];

        $uri  = "/api/file/store-uri";
        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param string $key
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function getFileInfo(string $key)
    {
        $this
            ->checkKey($key)
            ->runChecks();

        $options = [
            'json' => [
                "file_key" => $key,
            ],
        ];

        $uri  = "/api/file/info";
        // do the request as get
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param string $key
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function deleteFile(string $key)
    {
        $this
            ->checkKey($key)
            ->runChecks();

        $options = [
            'json' => [
                "file_key" => $key,
            ],
        ];

        $uri  = "/api/file/delete";
        // do the request as get
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param string $key
     * @param string $file
     * @return BestCdnResult
     * @throws BestCdnException
     * @throws \Throwable
     */
    public function updateFile(string $key, string $file)
    {
        $key = self::sanitizeFilename($key);

        $this
            ->checkKey($key)
            ->checkFile($file)
            ->runChecks();

        $fileHandle = fopen($file, 'r');
        $fileStats  = fstat($fileHandle);

        // upload files bigger than our upload limit to S3 otherwise upload them directly as binary
        if ($fileStats['size'] > self::UPLOAD_LIMIT) {
            return $this->updateViaS3($key, $this->getPreSignedURI($key, "update-or-create"), $fileHandle, md5_file($file));
        } else {
            return $this->updateViaS3($key, $this->getPreSignedURI($key, "update-or-create"), $fileHandle, md5_file($file)); // TODO: change this to binary later for performance optimization
        }
    }

    /**
     * @param string $key
     * @param string $file
     * @return BestCdnResult
     * @throws BestCdnException
     * @throws \Throwable
     */
    public function updateOrCreateFile(string $key, string $file)
    {
        $key = self::sanitizeFilename($key);

        $this
            ->checkKey($key)
            ->checkFile($file)
            ->runChecks();

        $fileHandle = fopen($file, 'r');
        $fileStats  = fstat($fileHandle);

        // upload files bigger than our upload limit to S3 otherwise upload them directly as binary
        if ($fileStats['size'] > self::UPLOAD_LIMIT) {
            return $this->updateOrCreateViaS3($key, $this->getPreSignedURI($key, "update-or-create"), $fileHandle, md5_file($file));
        } else {
            return $this->updateOrCreateViaS3($key, $this->getPreSignedURI($key, "update-or-create"), $fileHandle, md5_file($file)); // TODO: change this to binary later for performance optimization
        }
    }

    /**
     * @param string $key
     * @param string $uri
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function updateFileByUri(string $key, string $uri)
    {
        $key = self::sanitizeFilename($key);

        $this
            ->checkKey($key)
            ->checkUri($uri)
            ->runChecks();

        $options    = [
            'json' => [
                "file_key" => $key,
                "uri"      => $uri,
            ],
        ];

        $uri = "/api/file/update-uri";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param string $key
     * @param string $uri
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function updateOrCreateFileByUri(string $key, string $uri)
    {
        $key = self::sanitizeFilename($key);

        $this
            ->checkKey($key)
            ->checkUri($uri)
            ->runChecks();

        $options    = [
            'json' => [
                "file_key" => $key,
                "uri"      => $uri,
            ],
        ];

        $uri = "/api/file/update-or-create-uri";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param string $oldKey
     * @param string $newKey
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function renameFile(string $oldKey, string $newKey)
    {
        $oldKey = self::sanitizeFilename($oldKey);
        $newKey = self::sanitizeFilename($newKey);
        $this
            ->checkKey($oldKey)
            ->checkKey($newKey)
            ->runChecks();

        $options = [
            'json' => [
                "file_key"     => $oldKey,
                "file_key_new" => $newKey,
            ],
        ];

        $uri = "/api/file/rename";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param string $uid
     * @param array $visibilityOptions
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function changeFileVisibility(string $uid, array $visibilityOptions)
    {
        $this
            ->checkUid($uid)
            ->checkVisibilityOptions($visibilityOptions)
            ->runChecks();

        $options = [
            'headers' => [
                "Content-Type" => "application/json"
            ],
            'body' => json_encode($visibilityOptions),
        ];

        $uri = "/api/file/{$uid}/visibility";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param string $uid
     * @param array $expirationOptions
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function changeFileExpiration(string $uid, array $expirationOptions)
    {
        $this
            ->checkUid($uid)
            ->checkExpirationOptions($expirationOptions)
            ->runChecks();

        $options = [
            'headers' => [
                "Content-Type" => "application/json"
            ],
            'body' => json_encode($expirationOptions),
        ];

        $uri = "/api/file/{$uid}/expiration";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param string $uid
     * @param array $cacheOptions
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function updateFileCacheSettings(string $uid, array $cacheOptions)
    {
        $this
            ->checkUid($uid)
            ->checkCacheOptions($cacheOptions)
            ->runChecks();

        $options = [
            'headers' => [
                "Content-Type" => "application/json"
            ],
            'body' => json_encode($cacheOptions),
        ];

        $uri = "/api/file/{$uid}/cache/update";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }


    /**
     * @param array $listOptions
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function listAll(array $listOptions)
    {
        $this
            ->checkListOptions($listOptions)
            ->runChecks();

        $options = [
            'headers' => [
                "Content-Type" => "application/json"
            ],
            'body' => json_encode($listOptions),
        ];

        $uri = "/api/list/all";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param array $listOptions
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function listFiles(array $listOptions)
    {
        $this
            ->checkListOptions($listOptions)
            ->runChecks();

        $options = [
            'headers' => [
                "Content-Type" => "application/json"
            ],
            'body' => json_encode($listOptions),
        ];

        $uri = "/api/list/files";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

    /**
     * @param array $listOptions
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    public function listDirectories(array $listOptions)
    {
        $this
            ->checkListOptions($listOptions)
            ->runChecks();

        $options = [
            'headers' => [
                "Content-Type" => "application/json"
            ],
            'body' => json_encode($listOptions),
        ];

        $uri = "/api/list/directories";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        return $response;
    }

}
