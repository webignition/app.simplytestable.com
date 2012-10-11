<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use SimplyTestable\ApiBundle\Tests\BaseTestCase;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class BaseControllerJsonTestCase extends BaseSimplyTestableTestCase {   
    
    protected function createWebRequest() {
        $request = parent::createWebRequest();
        $request->headers->set('Accept', 'application/json');        
        return $request;
    }   
    
}
