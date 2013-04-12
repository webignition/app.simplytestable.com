<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class EnqueueCancellationForAwaitingCancellationCommandTest extends BaseSimplyTestableTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    } 
    
    
    public function testCancellationJobsAreEnqueued() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $jobId = $this->createAndPrepareJob($canonicalUrl);
        
        $taskIds = $this->getTaskIds($canonicalUrl, $jobId);        
        foreach ($taskIds as $taskId) {
            $task = $this->getTaskService()->getById($taskId);
            $task->setState($this->getTaskService()->getInProgressState());
            $this->getTaskService()->getEntityManager()->persist($task);
            $this->getTaskService()->getEntityManager()->flush();
        }
        
        $job = $this->getJobService()->getById($jobId);
        $this->getJobService()->getEntityManager()->refresh($job);

        $this->getJobController('cancelAction')->cancelAction($canonicalUrl, $jobId);
        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:enqueue-cancellation-for-awaiting-cancellation'));          
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskCancelCollectionJob',
            'task-cancel',
            array(
                'ids' => implode(',', $taskIds)
            )              
        ));
        
    }
     
    
    public function testExecuteInMaintenanceReadOnlyModeReturnsStatusCode1() {        
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));                
        $this->assertEquals(1, $this->runConsole('simplytestable:task:enqueue-cancellation-for-awaiting-cancellation'));      
    }

}