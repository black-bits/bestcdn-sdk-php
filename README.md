# BestCDN SDK PHP

PHP SDK to work with BestCDN.

It contains optional Laravel support.

**Currently unstable aka. work in progress, use at your own discretion.**

## How to install

To add this package to your app
```
composer require black-bits/bestcdn-sdk-php
```

Using Laravel you need to publish the config
```
php artisan vendor:publish --provider="BlackBits\BestCdn\BestCdnServiceProvider"
```

## How to use

### Laravel

Using Laravel you can use the SDK in multiple ways.

#### Using the Facade

```php
$key     = "myPath/myFileName.ext";      // desired path on cdn
$file    = __DIR__ . "/file.ext";        // local absolute path or php resource handle
$respose = BestCdn::putFile($key, file); 
```




#### Using Dependency Injection

For example inside a Controller

```php
class FileController extends Controller
{
    public function putFile(Request $request, BestCdn $cdn)
    {
        // ...
        $key     = "myPath/myFileName.ext"; // desired path on cdn
        $file    = __DIR__ . "/file.ext";   // local absolute path or php resource handle
        $respose = $cdn->putFile($key, file); 
        // ...
    }
}
```

### In other frameworks or standalone PHP
If you do not use the Laravel Framework you need to instantiate the base class with a default config first

```php
$config = [

    'connections' => [
        'default' => [
            "credentials" => [
                "key"    => "YourBestCdnProjectKey",    // required
                "secret" => "YourBestCdnProjectSecret", // required
            ],
            "defaultRequestOptions" => [
                "base_uri" => "https://management.bestcdn.io/", // required - e.g. sandbox(testing) or production endpoint
                "verify"   => true,                             // optional - can be set to false for local testing (does not enforce SSL verification)
            ],
        ],
    ],

    "defaultConnection" => "default", // optional, if you configure multiple connections (multiple projects/testing/production)
];

$cdn     = new BestCdnClient($config);
$key     = "myPath/myFileName.ext"; // desired path on cdn
$file    = __DIR__ . "/file.ext";   // local absolute path or php resource handle
$respose = $cdn->putFile($key, file);
```

### Making a request

#### putFile()
When making a request to store a file on the CDN you need to provide the desired **key** and a **file**.
  
The **key** represents the sub-path within your project namespace (the public path on the CDN will end in /{project-name}/{key}).

The **file** can be a php resource (e.g. an fopen() handle) or an absolute path to a file.
If you provide a resource handle it will be closed on successful upload to the CDN.

```php
$key     = "myPath/myFileName.md"; // desired path on cdn
$file    = __DIR__ . "/README.md"; // local absolute path or php resource handle
$respose = $cdn->putFile($key, file);
```

### Handling Results

After a successful request you get the response data like this:
```php
var_dump($response->data());
```
Results in:
```
array(1) {
  ["cdn_link"]=>
  string(139) "https://master.bestcdn.io/1/scGe6cSwttS64d3fNl34itK6IdephcDWYWWR4fFhdJVJyiRskU2bI2D80FzpO0fY7KBgyvmjZvNCPitE5Dd5fKkOC4s4mBWWY3KZ.md"
}
```
This will be extended to a full file object once development enters alpha stage.

To access file properties (like the cdn_link) you can use convenience methods like this:
```php
$response['cdn_link'];
$response->get('cdn_link');
```
### Handling Errors

For error handling we provide a standardised exception as well as default error handling.

You handle common errors like this:

```php
$cdn = new BestCdnClient($config);

$key  = "myPath/myFileName.md";
$file = __DIR__ . "/README.md";

$result = $cdn->putFile($key, $file);
if ( $result->hasError() ) {
    print $result->statusCode();
    print $result->message();
    // ... abort mission!
}

// ... normal code
```

Common errors will include routine, non critical errors like trying to get information on or deleting a non existing file.

Exceptions will be thrown if the error needs to be handled (authentication error, etc.).