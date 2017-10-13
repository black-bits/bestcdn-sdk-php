<?php

namespace BlackBits\BestCdn\Contracts;

use Psr\Http\Message\ResponseInterface;

interface ResponseContract
{
    public function getData() : array;

    public function getResponse() : ResponseInterface;

    public function getMessage() : string;

    public function getStatusCode() : int;

};
