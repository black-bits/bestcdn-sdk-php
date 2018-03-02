<?php

namespace BlackBits\BestCdn;

use BlackBits\BestCdn\Contracts\ResponseContract;
use BlackBits\BestCdn\Exception\BestCdnException;
use BlackBits\BestCdn\Http\BestCdnBadResponse;
use BlackBits\BestCdn\Http\BestCdnResponse;
use Psr\Http\Message\ResponseInterface;

use BlackBits\BestCdn\Contracts\ResultContract;
use BlackBits\BestCdn\Traits\ContainsData;

class BestCdnResult implements ResultContract
{
    use ContainsData;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var int
     */
    protected $statusCode;


    /**
     * BestCdnResult constructor.
     *
     * @param ResponseContract $response
     */
    public function __construct(ResponseContract $response)
    {
        $this->response   = $response->getResponse();
        $this->content    = $response->getData();
        $this->message    = $response->getMessage();
        $this->statusCode = $response->getStatusCode();
    }

    /**
     * Convenience method to create a result from different Guzzle objects or custom classes
     *
     * @param $response
     *
     * @return BestCdnResult
     * @throws BestCdnException
     */
    public static function create($response)
    {
        switch (get_class($response)) {
            case "GuzzleHttp\Exception\ClientException":
                return new self(new BestCdnBadResponse($response));
                break;
            case "GuzzleHttp\Psr7\Response":
                return new self(new BestCdnResponse($response));
                break;
            case "BlackBits\BestCdn\Testing\TestResponse":
                return new self($response);
                break;
            default:
                throw new BestCdnException("Unknown response class: " . get_class($response), 500, ['response' => $response]);
        }
    }


    /**
     * For ResultContract
     * @inheritdoc
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasKey($name)
    {
        return isset($this->content[$name]);
    }

    /**
     * For ResultContract
     * @inheritdoc
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this[$key];
    }

    /**
     * For ResultContract
     * @inheritdoc
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /*
     * Public methods
     */

    /**
     * Returns the http status code
     *
     * @return int
     */
    public function statusCode()
    {
        return $this->statusCode ?? 0;
    }

    /**
     * @return mixed
     */
    public function message()
    {
        return $this->message ?? "Message empty";
    }

    /**
     * Checks for http errors
     *
     * @return bool
     */
    public function hasError()
    {
        $validStatusCodes = [200,201];
        return (in_array($this->statusCode(), $validStatusCodes)) ? false : true;
    }

}
