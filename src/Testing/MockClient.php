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
                    "cdn-link" => "https://bb-cdn-staging.nyc3.digitaloceanspaces.com/1/CizhYodMfsih9v5msn6NNa9zDHEjDC62Z3NzrFXYBYcxTs1dMAWjyJakSLHSgwZaHMDGfQF2hfW9JDjx2QX5tmC5GCGeM2Mgvye1.mp4",
                ],
            ],
            "/api/file/store-uri" => [
                "data" => [
                    "cdn-link" => "https://bb-cdn-staging.nyc3.digitaloceanspaces.com/1/CizhYodMfsih9v5msn6NNa9zDHEjDC62Z3NzrFXYBYcxTs1dMAWjyJakSLHSgwZaHMDGfQF2hfW9JDjx2QX5tmC5GCGeM2Mgvye1.mp4",
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
         return new TestResponse($this->mockResults[$uri]);
    }
}
