<?php
namespace SimplyTestable\ApiBundle\Services\Postmark;

class TestSender extends Sender {   
    
    const DEFAULT_OK_MESSAGE = '{"To":"foo@example.com","SubmittedAt":"2014-02-20T05:51:07.4670731-05:00","MessageID":"19cf44c7-f3a4-47a7-8680-47a8c5c5c5b1","ErrorCode":0,"Message":"OK"}';

    
    /**
     * 
     * @param \MZ\PostmarkBundle\Postmark\Message $message
     * @return string
     */
    protected function getJsonRespnse(\MZ\PostmarkBundle\Postmark\Message $message) {
        return self::DEFAULT_OK_MESSAGE;
    }
    
}