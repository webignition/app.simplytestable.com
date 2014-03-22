<?php
namespace SimplyTestable\ApiBundle\Services\Postmark;

use SimplyTestable\ApiBundle\Model\Postmark\Response as PostmarkResponse;
use SimplyTestable\ApiBundle\Exception\Postmark\Response\Exception as PostmarkResponseException;

class Sender {
    
    /**
     *
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    private $history = null;
    
    
    /**
     * 
     * @param \MZ\PostmarkBundle\Postmark\Message $message
     * @return \SimplyTestable\ApiBundle\Model\Postmark\Response
     */
    public function send(\MZ\PostmarkBundle\Postmark\Message $message) {
        $response = new PostmarkResponse($this->getJsonRespnse($message));
        
        $this->getHistory()->add(array(
            'message' => $message,
            'response' => $response
        ));
        
        if ($response->isError()) {
            throw new PostmarkResponseException($response->getMessage(), $response->getErrorCode());
        }
        
        return $response;
    }
    
    
    /**
     * 
     * @return array
     */
    public function getHistory() {
        if (is_null($this->history)) {
            $this->history = new \Doctrine\Common\Collections\ArrayCollection();
        }
        
        return $this->history;
    }
    
    
    /**
     * 
     * @return \MZ\PostmarkBundle\Postmark\Message 
     */
    public function getLastMessage() {
        $lastHistoryItem = $this->getHistory()->last();
        return $lastHistoryItem['message'];
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Model\Postmark\Response
     */
    public function getLastResponse() {
        $lastHistoryItem = $this->getHistory()->last();
        return $lastHistoryItem['response'];        
    }
    
    
    /**
     * 
     * @param \MZ\PostmarkBundle\Postmark\Message $message
     * @return string
     */
    protected function getJsonRespnse(\MZ\PostmarkBundle\Postmark\Message $message) {
        return $message->send();
    }    
    
}