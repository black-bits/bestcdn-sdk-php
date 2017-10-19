<?php

if (!function_exists('sanitizeFilename')) {

    /**
     * Sanitizes a filename to be compliant with bestcdn.io expectations
     *
     * @param string $str
     *
     * @return string
     */
    function sanitizeFilename(string $str)
    {
        return \BlackBits\BestCdn\BestCdn::sanitizeFilename($str);
    }
}

if (!function_exists('isValidFilename')) {

    /**
     * Checks if a file is 100% compliant with bestcdn.io's expectations
     *
     * @param string $str
     *
     * @return bool
     */
    function isValidFilename(string $str)
    {
        return \BlackBits\BestCdn\BestCdn::isValidFilename($str);
    }
}
