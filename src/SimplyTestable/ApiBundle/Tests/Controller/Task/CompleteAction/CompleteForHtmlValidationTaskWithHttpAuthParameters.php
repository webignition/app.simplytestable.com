<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CompleteForHtmlValidationTaskWithHttpAuthParameters extends BaseControllerJsonTestCase {

    public function testReportCompletionWithHttpAuthParameters() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));    
        
        $this->createWorker();
        
        $task = $job->getTasks()->first();
        $this->assertNull($task->getOutput());
     
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        ));
        
        $this->assertEquals('task-in-progress', $task->getState()->getName());
        
        $this->getTaskController('completeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '{"messages":[]}',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $this->assertEquals($this->getTaskService()->getCompletedState(), $task->getState());
        $this->assertNotNull($task->getOutput());
    }

}

