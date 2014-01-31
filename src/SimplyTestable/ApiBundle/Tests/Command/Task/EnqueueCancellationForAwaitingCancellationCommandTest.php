<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class EnqueueCancellationForAwaitingCancellationCommandTest extends ConsoleCommandTestCase {
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:enqueue-cancellation-for-awaiting-cancellation';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(         
            new \SimplyTestable\ApiBundle\Command\Task\EnqueueCancellationForAwaitingCancellationCommand()
        );
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
        
        $this->assertReturnCode(0);
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskCancelCollectionJob',
            'task-cancel',
            array(
                'ids' => implode(',', $taskIds)
            )              
        ));
        
    }
     
    
    public function testExecuteInMaintenanceReadOnlyModeReturnsStatusCode1() {        
        $this->executeCommand('simplytestable:maintenance:enable-read-only');        
        $this->assertReturnCode(1);   
    }

}