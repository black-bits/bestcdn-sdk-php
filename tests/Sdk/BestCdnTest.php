<?php

namespace Tests\Sdk;

use BlackBits\BestCdn\BestCdn;
use PHPUnit\Framework\TestCase;

class BestCdnTest extends TestCase
{
    /**
     * @var \BlackBits\BestCdn\BestCdn
     */
    protected $bestCdn;

    private function bestCdn()
    {
        return $this->bestCdn ?: new BestCdn([

            'connections' => [
                'default' => [
                    "credentials" => [
                        "key"    => "key",
                        "secret" => "secret",
                    ],
                    "defaultRequestOptions" => [
                        "base_uri" => "https://staging.management.bestcdn.io/",
                        "verify"   => false,
                    ],
                ],
            ],

            "defaultConnection" => "mock-connection",
        ]);
    }

    public function testThatWeCanUploadAFile()
    {
        $this->assertEquals([
            "cdn-link" => "https://bb-cdn-staging.nyc3.digitaloceanspaces.com/1/CizhYodMfsih9v5msn6NNa9zDHEjDC62Z3NzrFXYBYcxTs1dMAWjyJakSLHSgwZaHMDGfQF2hfW9JDjx2QX5tmC5GCGeM2Mgvye1.mp4",
        ], $this->bestCdn()->putFile("test", __FILE__)->data());
        $this->assertEquals([
            "cdn-link" => "https://bb-cdn-staging.nyc3.digitaloceanspaces.com/1/CizhYodMfsih9v5msn6NNa9zDHEjDC62Z3NzrFXYBYcxTs1dMAWjyJakSLHSgwZaHMDGfQF2hfW9JDjx2QX5tmC5GCGeM2Mgvye1.mp4",
        ], $this->bestCdn()->putFileByUri("test", "test")->data());
    }
}
