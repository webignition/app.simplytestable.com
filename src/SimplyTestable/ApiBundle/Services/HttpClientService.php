<?php
namespace SimplyTestable\ApiBundle\Services;

use Guzzle\Http\Client as HttpClient;
use Guzzle\Plugin\Backoff\BackoffPlugin;
use Doctrine\Common\Cache\MemcacheCache;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Plugin\Cache\CachePlugin;

class HttpClientService { 
    
    
    /**
     *
     * @var \Guzzle\Http\Client
     */
    protected $httpClient = null;     
    
    
    /**
     *
     * @var array
     */
    private $curlOptions = array();
    
    
    /**
     * 
     * @param array $curlOptions
     */
    public function __construct($curlOptions) {
        foreach ($curlOptions as $curlOption) {
            if (defined($curlOption['name'])) {
                $this->curlOptions[constant($curlOption['name'])] = $curlOption['value'];
            }
        }
    }
    
    
    public function reset() {
        $this->httpClient = null;
    }
    
    
    public function get() {
        if (is_null($this->httpClient)) {            
            $this->httpClient = new HttpClient();
            
            $this->httpClient->addSubscriber(BackoffPlugin::getExponentialBackoff(
                    3,
                    array(500, 503, 504)
            ));
            
            $this->httpClient->addSubscriber(new \Guzzle\Plugin\History\HistoryPlugin());
        }
        
        return $this->httpClient;
    }
    
    
    /**
     * 
     * @param string $uri
     * @param array $headers
     * @param string $body
     * @return \Guzzle\Http\Message\Request
     */
    public function getRequest($uri = null, $headers = null, $body = null) {        
        $request = $this->get()->get($uri, $headers, $body);        
        $request->setHeader('Accept-Encoding', 'gzip,deflate');
        
        foreach ($this->curlOptions as $key => $value) {
            $request->getCurlOptions()->set($key, $value);
        }
        
        return $request;
    }
    
    
    /**
     * 
     * @param string $uri
     * @param array $headers
     * @param array $postBody
     * @return \Guzzle\Http\Message\Request
     */
    public function postRequest($uri = null, $headers = null, $postBody = null) {
        $request = $this->get()->post($uri, $headers, $postBody);        

        foreach ($this->curlOptions as $key => $value) {
            $request->getCurlOptions()->set($key, $value);
        }        
        
        return $request;        
    }
    
}