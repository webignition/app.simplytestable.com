<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\ListAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

abstract class AbstractListTest extends BaseControllerJsonTestCase { 
    
    protected function getCanonicalUrlCollection($count = 1) {
        $canonicalUrlCollection = array();
        
        for ($index = 0; $index < $count; $index++) {
            $canonicalUrlCollection[] = 'http://' . ($index + 1) . '.example.com/';
        }
        
        return $canonicalUrlCollection;
    }      
    
}


