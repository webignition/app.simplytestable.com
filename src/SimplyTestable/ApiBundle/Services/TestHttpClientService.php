<?php
namespace SimplyTestable\ApiBundle\Services;

use Guzzle\Http\Client as HttpClient;

class TestHttpClientService extends HttpClientService {     
    
    public function get() {
        if (is_null($this->httpClient)) {            
            $this->httpClient = new HttpClient();
            $this->httpClient->addSubscriber(new \Guzzle\Plugin\History\HistoryPlugin());            
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
    
    
    /**
     * 
     * @return \Guzzle\Plugin\History\HistoryPlugin
     */
    public function getHistoryPlugin() {
        return $this->getPluginByClassAndEvent('Guzzle\Plugin\History\HistoryPlugin', 'request.sent');
    }
    
    
    
    private function getPluginByClassAndEvent($class, $event) {
        $listeners = $this->get()->getEventDispatcher()->getListeners($event);
        
        foreach ($listeners as $listener) {            
            if (get_class($listener[0]) == $class) {
                return $listener[0];
            }
        }        
    }
    
}