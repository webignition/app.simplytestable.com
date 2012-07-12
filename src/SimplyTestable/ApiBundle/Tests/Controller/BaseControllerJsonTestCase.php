<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

class BaseControllerJsonTestCase extends BaseControllerTestCase {   
    
    protected function createWebRequest() {
        $request = parent::createWebRequest();
        $request->headers->set('Accept', 'application/json');        
        return $request;
    }   
    
}
