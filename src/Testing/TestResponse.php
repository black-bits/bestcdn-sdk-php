<?php

namespace BlackBits\BestCdn\Testing;

use BlackBits\BestCdn\Contracts\ResponseContract;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class TestResponse implements ResponseContract
{
    /**
     * @var array
     */
    protected $data;

    /**
     * TestResponse constructor.
     *
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }


    /*
     * Accessors
     */

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return new Response();
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return "Mock Response for unit testing";
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return 200;
    }

}
