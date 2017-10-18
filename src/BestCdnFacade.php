<?php

namespace BlackBits\BestCdn;

use Illuminate\Support\Facades\Facade;


class BestCdnFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return BestCdn::class;
    }
}