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
     * Ensures that a client and returns it
     *
     * @return Client|MockClient
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
     *
     * @return BestCdnResult
     *
     * @throws BestCdnException
     */
    protected function request(string $method, $uri, array $options = [])
    {
        $options['headers'] = array_merge([
            'User-Agent'    => 'BlackBits-BestCDN-SDK-PHP/1.0',
            'Accept'        => 'application/json',
            'Authorization' => $this->getAuthToken(),
        ], empty($options['headers']) ? [] : $options['headers']);

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
     * Run a request on a specified connection
     *
     * @param string $connection
     *
     * @return $this
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
     * @param string|resource $file
     *
     * @return BestCdnResult
     */
    public function putFile(string $key, $file)
    {
        $key = self::sanitizeFilename($key);

        $this
            ->checkKey($key)
            ->checkFile($file)
            ->runChecks();

        $fileHandle = is_resource($file) ? $file : fopen($file, 'r');
        $options = [
            'multipart' => [
                [
                    'file_key' => $key,
                    'file'     => $fileHandle,
                ],
            ],
        ];
        $uri = "/api/file/store-bin";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        if (is_resource($fileHandle)){
            fclose($fileHandle);
        }

        return $response;
    }

    /**
     * @param string $key
     * @param string $uri
     *
     * @return BestCdnResult
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
     * @param string|resource $file
     *
     * @return BestCdnResult
     */
    public function updateFile(string $key, $file)
    {
        $key = self::sanitizeFilename($key);

        $this
            ->checkKey($key)
            ->checkFile($file)
            ->runChecks();

        $fileHandle = is_resource($file) ? $file : fopen($file, 'r');
        $options = [
            'multipart' => [
                [
                    'file_key' => $key,
                    'file'     => $fileHandle,
                ],
            ],
        ];

        $uri = "/api/file/update-bin";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        if (is_resource($fileHandle)){
            fclose($fileHandle);
        }

        return $response;
    }

    /**
     * @param string $key
     * @param string $uri
     *
     * @return BestCdnResult
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
     * @param string $oldKey
     * @param string $newKey
     *
     * @return BestCdnResult
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
