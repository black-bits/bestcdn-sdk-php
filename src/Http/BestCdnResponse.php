<?php

namespace BlackBits\BestCdn\Http;

use BlackBits\BestCdn\Contracts\ResponseContract;
use BlackBits\BestCdn\Traits\HandlesJson;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\ResponseInterface;

class BestCdnResponse implements ResponseContract
{
    use HandlesJson;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * BestCdnResponse constructor.
     *
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getData(): array
    {
        return $this->parseJson($this->response->getBody()->getContents());
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getMessage(): string
    {
        return $this->getResponse()->getReasonPhrase();
    }

    public function getStatusCode(): int
    {
        return $this->getResponse()->getStatusCode();
    }
}
