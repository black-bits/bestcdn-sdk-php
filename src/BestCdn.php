<?php

namespace BlackBits\BestCdn;

use BlackBits\BestCdn\Exception\BestCdnException;

use Psr\Http\Message\UriInterface;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

class BestCdn
{
    /**
     * @var array
     */
    protected $config = [];

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
        $this->validateConfig();
        $this->connection = empty($config['defaultConnection']) ?: "default";
    }

    /**
     * Ensures that a client and returns it
     *
     * @return Client
     */
    protected function client()
    {
        return $this->httpClient ?: new Client($this->config['connections']['default']['defaultRequestOptions'] ?: []);
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
     * @throws BestCdnException
     */
    protected function validateConfig()
    {
        if (empty($this->config['connections'])) {
            throw new BestCdnException("Invalid configuration", 500, ['errors' => ['missing_field' => 'connections']]);
        }

        foreach ($this->config['connections'] as $connection => $values) {
            $this->validateConnection($connection);
        }
    }


    /**
     * Checks if a connection exists and is properly configured
     *
     * @param string $connection
     *
     * @throws BestCdnException
     */
    protected function validateConnection(string $connection)
    {
        if (empty($this->config['connections'][$connection])) {
            throw new BestCdnException("Connection not found", 500, ['connection' => $connection]);
        }

        $errors = [];
        $config = $this->config['connections'][$connection];
        if (empty($config['credentials']['key'])) {
            $errors['credentials']['key'] = "'credentials.key' config entry missing for '{$connection}' connection";
        }

        if (empty($config['credentials']['secret'])) {
            $errors['credentials']['secret'] = "'credentials.secret' config entry missing for '{$connection}' connection";
        }

        if (empty($config['defaultRequestOptions']['base_uri'])) {
            $errors['defaultRequestOptions']['base_uri'] = "'defaultRequestOptions.base_uri' config entry missing for '{$connection}' connection";
        }

        if ($errors) {
            throw new BestCdnException("Invalid configuration", 500, ['errors' => $errors]);
        }
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

    /**
     * Stores a file on the CDN
     *
     * @param string          $key      Desired file name/path
     * @param string|resource $file     The file handle or path
     *
     * @return BestCdnResult
     */
    public function putFile($key, $file)
    {
        $fileHandle = is_resource($file) ? $file : fopen($file, 'r');
        $options    = [
            'headers' => [
                'filename' => $key
            ],
            'body'    => $fileHandle,
        ];
        $uri        = "/api/file/store";

        // do the request as post
        $response = $this->request('POST', $uri, $options);

        if (is_resource($fileHandle)){
            fclose($fileHandle);
        }

        return $response;
    }
}