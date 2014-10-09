<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class InvalidInputTest extends BaseControllerJsonTestCase {
    
    
    /**
     *
     * @var array
     */
    private $taskCompletionData = array(
        'end_date_time' => '2012-03-08 17:03:00',
        'output' => '[]',
        'contentType' => 'application/json',
        'state' => 'completed',
        'errorCount' => 0,
        'warningCount' => 0          
    );      
    
    public function testWithInvalidTaskType() {        
        $response = $this->getTaskController('completeAction', $this->taskCompletionData)->completeAction('http://example.com', 'foo', '');
        $this->assertEquals(400, $response->getStatusCode());
    }    
    
    public function testWithUrlMatchingNoTasks() {        
        $response = $this->getTaskController('completeAction', $this->taskCompletionData)->completeAction('http://example.com', 'HTML validation', '');
        $this->assertEquals(410, $response->getStatusCode());
    }
    
    public function testWithInvalidUrl() {        
        $response = $this->getTaskController('completeAction', $this->taskCompletionData)->completeAction('foo', 'HTML validation', '');
        $this->assertEquals(410, $response->getStatusCode());
    }    
    
}

