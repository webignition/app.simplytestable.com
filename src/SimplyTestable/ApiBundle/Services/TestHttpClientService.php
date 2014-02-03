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
    
    
    public function queueFixtures($fixtures) {
        foreach ($fixtures as $fixture) {
            $this->queueFixture($fixture);
        }
    }
    
    
    public function queueFixture($fixture) {
        if ($fixture instanceof \Exception) {             
            $this->getMockPlugin()->addException($fixture);
        } else {
            $this->getMockPlugin()->addResponse($fixture);
        }
    }
    
    
    
    /**
     * 
     * @return \Guzzle\Plugin\Mock\MockPlugin
     */
    public function getMockPlugin() {
        if (!$this->hasMockPlugin()) {
            $this->get()->addSubscriber(new \Guzzle\Plugin\Mock\MockPlugin()); 
        }
        
        $beforeSendListeners = $this->get()->getEventDispatcher()->getListeners('request.before_send');
        
        foreach ($beforeSendListeners as $beforeSendListener) {
            if (get_class($beforeSendListener[0]) == 'Guzzle\Plugin\Mock\MockPlugin') {
                return $beforeSendListener[0];
            }
        }
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasMockPlugin() {
        $beforeSendListeners = $this->get()->getEventDispatcher()->getListeners('request.before_send');
        
        foreach ($beforeSendListeners as $beforeSendListener) {
            if (get_class($beforeSendListener[0]) == 'Guzzle\Plugin\Mock\MockPlugin') {
                return true;
            }
        }
        
        return false;     
    }
    
}