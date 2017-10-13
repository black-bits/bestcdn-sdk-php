<?php

namespace BlackBits\BestCdn\Http;

use BlackBits\BestCdn\Contracts\ResponseContract;
use BlackBits\BestCdn\Traits\HandlesJson;
use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\ResponseInterface;

class BestCdnBadResponse implements ResponseContract
{
    use HandlesJson;

    /**
     * @var BadResponseException|null
     */
    protected $badResponseException;

    /**
     * BestCdnBadResponse constructor.
     *
     * @param BadResponseException|null $badResponseException
     */
    public function __construct(BadResponseException $badResponseException)
    {
        $this->badResponseException = $badResponseException;
    }

    public function getData(): array
    {
        return $this->parseJson($this->badResponseException->getResponse()->getBody()->getContents());
    }

    public function getResponse(): ResponseInterface
    {
        return $this->badResponseException->getResponse();
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
