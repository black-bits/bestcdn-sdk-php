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
            "cdn_link" => "https://staging.master.bestcdn.io/project_1-customer_01/test",
        ], $this->bestCdn()->putFile("test", __FILE__)->data());

        $this->assertEquals([
            "cdn_link" => "https://staging.master.bestcdn.io/project_1-customer_01/dir/test.jpg",
        ], $this->bestCdn()->putFile("dir/test.jpg", fopen(__FILE__, "r"))->data());

        $this->assertEquals([
            "cdn_link" => "https://staging.master.bestcdn.io/project_1-customer_01/test.jpg",
        ], $this->bestCdn()->putFileByUri("test.jpg", "test")->data());
    }

    public function testThatWeReceiveACdnLink()
    {
        $this->assertEquals("https://staging.master.bestcdn.io/project_1-customer_01/dir1/test.jpg",
            $this->bestCdn()->putFile("dir1/test.jpg", __FILE__)->get("cdn_link"));

        $this->assertEquals("https://staging.master.bestcdn.io/project_1-customer_01/dir2/test.jpg",
            $this->bestCdn()->putFile("dir2/test.jpg", fopen(__FILE__, "r"))->get("cdn_link"));

        $this->assertEquals("https://staging.master.bestcdn.io/project_1-customer_01/dir3/test.jpg",
            $this->bestCdn()->putFileByUri("dir3/test.jpg", "test")->get("cdn_link"));

    }
}
