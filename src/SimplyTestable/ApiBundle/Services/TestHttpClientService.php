<?php
namespace SimplyTestable\ApiBundle\Services;

use Guzzle\Http\Client as HttpClient;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Doctrine\Common\Cache\MemcacheCache;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Plugin\Cache\CachePlugin;

class TestHttpClientService extends HttpClientService {     
    
    public function get($baseUrl = '', $config = null) {
        if (is_null($this->httpClient)) {
            $this->httpClient = new HttpClient($baseUrl, $config);     
        }
        
        return $this->httpClient;
    }
    
}