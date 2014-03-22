<?php

namespace SimplyTestable\ApiBundle\Model\Postmark;

/**
{
   "ErrorCode":300,
   "Message":"Invalid 'To' address: 'foo@example'."
}

{
   "To":"foo@example.com",
   "SubmittedAt":"2014-02-20T05:51:07.4670731-05:00",
   "MessageID":"19cf44c7-f3a4-47a7-8680-47a8c5c5c5b1",
   "ErrorCode":0,
   "Message":"OK"
}
 */
class Response {
    
    const NON_ERROR_RESPONSE_MESSAGE = 'OK';
    const ERROR_CODE_INVALID_TO_ADDRESS = 300;
    
    /**
     *
     * @var \stdClass 
     */
    private $rawResponseData;    
    
    
    /**
     * 
     * @param string $jsonResponse
     */
    public function __construct($jsonResponse) {
        $this->rawResponseData = json_decode($jsonResponse);
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function isError() {
        return $this->getMessage() != self::NON_ERROR_RESPONSE_MESSAGE;
    }
    
    
    /**
     * 
     * @return int|null
     */
    public function getErrorCode() {
        if (!$this->isError()) {
            return null;
        }
        
        return $this->rawResponseData->ErrorCode;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getMessage() {
        return $this->rawResponseData->Message;
    }
}