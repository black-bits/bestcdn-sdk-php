<?php

namespace BlackBits\BestCdn\Traits;


use BlackBits\BestCdn\Exception\BestCdnException;

trait HandlesJson
{
    /**
     * Json decodes payload and checks for validity
     *
     * @param string $json
     *
     * @return mixed
     * @throws BestCdnException
     */
    public function parseJson(string $json)
    {
        $jsonPayload = json_decode($json, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new BestCdnException('Error parsing Result JSON', 500, ['errors' => ["json" => json_last_error_msg()]]);
        }

        return $jsonPayload;
    }
}
