<?php

namespace BlackBits\BestCdn;

use BlackBits\BestCdn\Traits\BestCdnHelpers;

class BestCdnFile
{
    use BestCdnHelpers;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var BestCdn
     */
    protected $bestCdn;

    /**
     * BestCdnFile constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Ensure a bestCdn instance
     *
     * @return BestCdn
     */
    protected function bestCdn()
    {
        $this->validateConfig();
        return $this->bestCdn ?: new BestCdn($this->config);
    }

    /*
     * Setters
     */

    /**
     * Setter for config
     *
     * @param $config
     */
    public function setConfig($config)
    {
        $this->validateConfig();
        $this->config = $config;
    }

    /*
     * Accessors
     */

    /**
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function uid()
    {
        return empty($this->data()['uid']) ? "" : $this->data()['uid'];
    }



    /*
     * API endpoints
     */

    /**
     * Delete the current file
     *
     * @return BestCdnResult
     */
    public function delete()
    {
        return $this->bestCdn()->deleteFile($this->uid());
    }

}