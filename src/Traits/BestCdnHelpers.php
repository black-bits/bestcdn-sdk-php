<?php

namespace BlackBits\BestCdn\Traits;

use BlackBits\BestCdn\Exception\BestCdnException;

trait BestCdnHelpers
{
    /**
     * @var array
     */
    protected $config = [];

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


    /*
     * Static convenience methods
     */

    /**
     * Sanitizes a filename to be compliant with bestcdn.io expectations
     *
     * @param string $str
     *
     * @return string
     */
    public static function sanitizeFilename(string $str)
    {
        $str = strtolower($str);
        $str = str_replace(" ", "_", $str);
        $str = preg_replace('/[^A-Za-z0-9_\-.\/]/', "", $str);
        return trim($str, "/");
    }

    /**
     * Checks if a file is 100% compliant with bestcdn.io's expectations
     *
     * @param string $str
     *
     * @return bool
     */
    public static function isValidFilename(string $str)
    {
        return $str === self::sanitizeFilename($str);
    }
}