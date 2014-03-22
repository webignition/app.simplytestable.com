<?php
namespace SimplyTestable\ApiBundle\Services\Mail;

class Service {
    
    /**
     *
     * @var \MZ\PostmarkBundle\Postmark\Message
     */
    private $postmarkMessage;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\Postmark\Sender 
     */
    private $postmarkSender;

    
    /**
     * 
     * @param \MZ\PostmarkBundle\Postmark\Message $postmarkMessage
     */
    public function __construct(
        \MZ\PostmarkBundle\Postmark\Message $postmarkMessage,
        \SimplyTestable\ApiBundle\Services\Postmark\Sender $postmarkSender) {        
    
        $this->postmarkMessage = clone $postmarkMessage;
        $this->postmarkSender = $postmarkSender;
    }    
    
    
    /**
     * 
     * @return \MZ\PostmarkBundle\Postmark\Message
     */
    public function getNewMessage() {
        return clone $this->postmarkMessage;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\Postmark\Sender
     */
    public function getSender() {
        return $this->postmarkSender;
    }    
    
}