<?php

namespace BlackBits\BestCdn\Traits;


use BlackBits\BestCdn\Exception\BestCdnException;

trait RunsChecks
{
    /**
     * @var array
     */
    protected $checkErrors = [];

    /**
     * @throws BestCdnException
     */
    public function runChecks()
    {
        if (!empty($this->checkErrors)) {
            throw new BestCdnException("Some checks returned errors", 500, ['errors' => $this->checkErrors]);
        }
    }

    /**
     * @return array
     */
    public function checkErrors()
    {
        return $this->checkErrors;
    }


    /**
     * @param $file string|resource
     *
     * @return $this
     */
    public function checkFile($file)
    {
        if (empty($file)) {
            $errors['file'] = "file missing";
        }
        if (!is_resource($file) && !is_readable($file)) {
            $errors['file'] = "file is not readable";
        }
        return $this;
    }

    /**
     * @param $key string
     *
     * @return $this
     */
    public function checkKey($key)
    {
        if (empty($key)) {
            $errors['key'] = "key missing";
        }
        return $this;
    }

    /**
     * @param $uri
     *
     * @return $this
     */
    public function checkUri($uri)
    {
        if (empty($uri)) {
            $errors['uri'] = "uri missing";
        }
        return $this;
    }

    /**
     * @param $uid string
     *
     * @return $this
     */
    public function checkUid($uid)
    {
        if (empty($uid)) {
            $errors['uid'] = "uid missing";
        }
        return $this;
    }

    /**
     * @param $visibilityOptions array
     *
     * @return $this
     */
    public function checkVisibilityOptions($visibilityOptions)
    {
        if (empty($visibilityOptions)) {
            $errors['visibilityOptions'] = "visibilityOptions missing";
        }
        return $this;
    }

    /**
     * @param $expirationOptions
     *
     * @return $this
     */
    public function checkExpirationOptions($expirationOptions)
    {
        if (empty($expirationOptions)) {
            $errors['expirationOptions'] = "expirationOptions missing";
        }
        return $this;
    }

    /**
     * @param $cacheOptions
     *
     * @return $this
     */
    public function checkCacheOptions($cacheOptions)
    {
        if (empty($cacheOptions)) {
            $errors['cacheOptions'] = "cacheOptions missing";
        }
        return $this;
    }

    /**
     * @param $listOptions
     *
     * @return $this
     */
    public function checkListOptions($listOptions)
    {
        if (empty($listOptions)) {
            $errors['listOptions'] = "listOptions missing";
        }
        return $this;
    }

}
