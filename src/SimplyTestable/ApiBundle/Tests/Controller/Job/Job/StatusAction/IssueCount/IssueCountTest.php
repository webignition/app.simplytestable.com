<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction\IssueCount;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

abstract class IssueCountTest extends BaseControllerJsonTestCase {
    
    const CANONICAL_URL = 'http://example.com/';

    protected $jobData;    
    
    protected function getActionName() {
        return 'statusAction';
    }
    
    
    public function setUp() {
        parent::setUp();
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        foreach ($job->getTasks() as $task) {
            $this->getTaskController('completeByUrlAndTaskTypeAction', array(
                'end_date_time' => '2012-03-08 17:03:00',
                'output' => '[]',
                'contentType' => 'application/json',
                'state' => 'completed',
                'errorCount' => $this->getReportedErrorCount(),
                'warningCount' => $this->getReportedWarningCount()
            ))->completeByUrlAndTaskTypeAction($task->getUrl(), $task->getType()->getName(), $task->getParametersHash());            
        }
        
        $response = $this->getJobController('statusAction')->statusAction(self::CANONICAL_URL, $job->getId());
        $this->jobData = json_decode($response->getContent());
    }
    
    abstract protected function getReportedErrorCount();
    abstract protected function getReportedWarningCount();
    
    public function testErrorCount() {        
        $this->assertEquals($this->jobData->task_count * $this->getReportedErrorCount(), $this->jobData->error_count);
    }
    
    public function testWarningCount() {        
        $this->assertEquals($this->jobData->task_count * $this->getReportedWarningCount(), $this->jobData->warning_count);
    }    
}