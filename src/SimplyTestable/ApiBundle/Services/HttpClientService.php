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
    
    
    public function prepareRequest(\Guzzle\Http\Message\Request $request, $parameters = array()) {        
        $parameterBag = new \Symfony\Component\HttpFoundation\ParameterBag($parameters);
        
        $this->setRequestAuthentication($request, $parameterBag);
        $this->setRequestCookies($request, $parameterBag);
    }
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @param \Symfony\Component\HttpFoundation\ParameterBag $parameters
     */
    private function setRequestAuthentication(\Guzzle\Http\Message\Request $request, \Symfony\Component\HttpFoundation\ParameterBag $parameters) {
        if ($parameters->has('http-auth-username') || $parameters->has('http-auth-password')) {            
            $request->setAuth(
                ($parameters->has('http-auth-username')) ? $parameters->get('http-auth-username') : '',
                ($parameters->has('http-auth-password')) ? $parameters->get('http-auth-password') : '',
                'any'
            );
        }
    }      
    
    
    
    /**
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @param \Symfony\Component\HttpFoundation\ParameterBag $parameters
     */
    private function setRequestCookies(\Guzzle\Http\Message\Request $request, \Symfony\Component\HttpFoundation\ParameterBag $parameters) {
        if (!is_null($request->getCookies())) {
            foreach ($request->getCookies() as $name => $value) {
                $request->removeCookie($name);
            }
        }
        
        if ($parameters->has('cookies')) {
            $cookieUrlMatcher = new \webignition\Cookie\UrlMatcher\UrlMatcher();
            
            foreach ($parameters->get('cookies') as $cookie) {                
                if ($cookieUrlMatcher->isMatch($cookie, $request->getUrl())) {
                    $request->addCookie($cookie['name'], $cookie['value']);
                }
            }             
        }

    }   
    
}