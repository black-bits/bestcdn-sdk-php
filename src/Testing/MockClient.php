<?php

namespace BlackBits\BestCdn\Testing;

class MockClient
{
    /**
     * @var array
     */
    protected $mockResults;

    /**
     * MockClient constructor.
     */
    public function __construct()
    {
        $uid = "test-uid";
        $this->mockResults = [
            "/api/file/store-bin" => [
                "data" => [
                    "cdn_link" => "https://staging.master.bestcdn.io/project_1-customer_01/",
                ],
            ],
            "/api/file/store-uri" => [
                "data" => [
                    "cdn_link" => "https://staging.master.bestcdn.io/project_1-customer_01/",
                ],
            ],
            "/api/file/{$uid}/info" => [],
            "/api/file/{$uid}/delete" => [],
            "/api/file/{$uid}/update" => [],
            "/api/file/{$uid}/visibility" => [],
            "/api/file/{$uid}/expiration" => [],
            "/api/file/{$uid}/cache/update" => [],
            "/api/list/all" => [],
            "/api/list/files" => [],
            "/api/list/directories" => [],
        ];
    }


    public function request($method, $uri = '', array $options = [])
    {

        switch ($uri) {
            case "/api/file/store-bin":
            case "/api/file/store-uri":
                $this->mockResults[$uri]["data"]["cdn_link"] .= $options['multipart'][0]['contents'];
                return new TestResponse($this->mockResults[$uri]);
            default:
                return new TestResponse($this->mockResults[$uri]);
        }

    }
}
