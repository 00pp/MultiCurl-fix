<?php

use \Curl\MultiCurl;

require __DIR__ . '/../vendor/autoload.php';

define('USE_PROXY', false);

$multi_curl = new MultiCurl();

$multi_curl->success(function($instance) use (&$db, &$p) {
    
    print_r($instance -> response);
    //echo $instance -> url . ' - ' . strlen($instance->response).PHP_EOL;
  
});
$multi_curl->error(function($instance) {
    echo $instance->url . ' was unsuccessful. ';
    echo 'error code: ' . $instance->errorCode . "\n";
    // echo 'error message: ' . $instance->errorMessage . "\n";
});
$multi_curl->complete(function($instance) {
   echo 'call completed' . "\n";
});

$multi_curl -> setOpt(CURLOPT_FOLLOWLOCATION, true);
$multi_curl -> setOpt(CURLOPT_CONNECTTIMEOUT, 0); 
$multi_curl -> setOpt(CURLOPT_TIMEOUT, 15); //timeout in seconds
//$multi_curl -> setOpt(CURLOPT_SSL_VERIFYHOST, false);
//$multi_curl -> setOpt(CURLOPT_SSL_VERIFYPEER, false);

if (USE_PROXY == true) {
   
    $h = file_get_contents('./proxy-list.txt');
    $proxies = explode("\n",trim($h));
    //$multi_curl -> setOpt(CURLOPT_PROXYUSERPWD, 'user:pas');
    //$multi_curl -> setOpt(CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
} else 
    $proxies = false;

$multi_curl -> setRetry(function ($instance) use ($proxies) {
    $allowed_errCodes = [301,302,404]; // ignore these errors
    if (!in_array($instance->errorCode,$allowed_errCodes)) {
        
        if (USE_PROXY == true)
            $instance->setOpt(CURLOPT_PROXY, array_rand(array_flip($proxies)));
      
        echo 'retry '.$instance->errorCode.PHP_EOL;
        return $instance->retries < 5;  // how many times to retry 
    }
});

$time_start = microtime(true); 


$counter = 0; 
$threads = 10;
$rounds = 10;

for ($x=0; $x < $rounds; $x++) { 
    for ($i=0; $i < $threads; $i++) {
        echo $i.PHP_EOL;

        $url = 'https://api.ipify.org/#';
        //$url = 'http://httpbin.org/ip#';
        //$url = 'http://localhost:8000/test.html#';

        if (USE_PROXY == true)
            $multi_curl -> addGet($url.rand(1,5724), array_rand(array_flip($proxies)));
        else
            $multi_curl -> addGet($url.rand(1,5724));
    }
    $multi_curl->start(); // Blocks until all items in the queue have been processed

    echo PHP_EOL.' == DONE == '.PHP_EOL;
}

