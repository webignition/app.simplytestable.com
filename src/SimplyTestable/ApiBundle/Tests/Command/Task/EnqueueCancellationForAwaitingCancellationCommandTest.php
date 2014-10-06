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
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        
        foreach ($job->getTasks() as $task) {
            $task->setState($this->getTaskService()->getInProgressState());
            $this->getTaskService()->getManager()->persist($task);
        }
        
        $this->getTaskService()->getManager()->flush();
        $this->getJobService()->getManager()->refresh($job);
        $this->cancelJob($job);
        
        $this->assertReturnCode(0);
        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-cancel-collection',
            array(
                'ids' => implode(',', $this->getTaskIds($job))
            )              
        ));        
    }
     
    
    public function testExecuteInMaintenanceReadOnlyModeReturnsStatusCode1() {        
        $this->executeCommand('simplytestable:maintenance:enable-read-only');        
        $this->assertReturnCode(1);   
    }

}