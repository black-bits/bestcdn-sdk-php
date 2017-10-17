<?php
return [

    /*
     |--------------------------------------------------------------------------
     | Connections
     |--------------------------------------------------------------------------
     |
     | The different connections you want to use, they need to contain your credentials for authentication
     |
     */
    "connections" => [

        "default" => [
            "credentials" => [
                "key"    => env("BESTCDN_KEY", "YourProjectKey"),
                "secret" => env("BESTCDN_SECRET", "YourProjectSecret"),
            ],
            "defaultRequestOptions" => [
                "base_uri" => env("BESTCDN_BASE_URI", "https://management.bestcdn.io/"),
                "verify"   => env("BESTCDN_VERIFY_SSL", true),
            ],
        ],
    ],

    "defaultConnection" => "default",

];
