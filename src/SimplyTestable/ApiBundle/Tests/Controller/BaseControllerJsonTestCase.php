<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\BaseTestCase;

class BaseControllerJsonTestCase extends BaseTestCase {   
    
    protected function createWebRequest() {
        $request = parent::createWebRequest();
        $request->headers->set('Accept', 'application/json');        
        return $request;
    }   
    
}
