<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseTestCase;

class JobPreapreCommandTest extends BaseTestCase {

    public function testPrepareNewJob() {        
        $this->setupDatabase();
        
        $this->createJob('http://example.com');
        // run prepare command
        // examine job tasks
    }
    
    private function createJob($canonicalUrl) {
        $controllerName = 'SimplyTestable\ApiBundle\Controller\TestsController';
        $controller = $this->createController($controllerName);                       
        return $controller->startAction($canonicalUrl);
    }

}
