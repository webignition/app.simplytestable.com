<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class AssignCommandTest extends ConsoleCommandTestCase {
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:assign';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand(),
            new \SimplyTestable\ApiBundle\Command\TaskAssignCommand()
        );
    }   

    public function testAssignValidTaskReturnsStatusCode0() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        
        $this->assertReturnCode(0, array(
            'id' => $taskIds[0]
        ));
        
        $job = json_decode($this->fetchJob($canonicalUrl, $job_id)->getContent());
        $this->assertEquals('in-progress', $job->state);
    }
    
    
    public function testAssignTaskInWrongStateReturnsStatusCode1() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $task->setState($this->getTaskService()->getCompletedState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertReturnCode(1, array(
            'id' => $task->getId()
        )); 
    }
    
    public function testAssignTaskWhenNoWorkersReturnsStatusCode2() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        
        $this->assertReturnCode(2, array(
            'id' => $taskIds[0]
        ));
        
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $taskIds[0]
            )
        ));          
    }    
    
    
    public function testAssignTaskWhenNoWorkersAreAvailableReturnsStatusCode3() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        $this->createWorker('http://lithium.worker.simplytestable.com');
        $this->createWorker('http://helium.worker.simplytestable.com');

        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);
        
        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $task->setState($this->getTaskService()->getQueuedState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();

        $this->assertReturnCode(3, array(
            'id' => $taskIds[0]
        ));        
        
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $taskIds[0]
            )
        ));         
    }     
    
    
    public function testAssignInvalidTaskReturnsStatusCode4() {        
        $this->assertReturnCode(4, array(
            'id' => -1
        ));         
    }


    public function testAssignInMaintenanceReadOnlyModeReturnsStatusCode5() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');        
        $this->assertReturnCode(5, array(
            'id' => 1
        ));
    } 
    
    public function testAssignRaisesHttpClientErrorWithOnlyOneWorker() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');

        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);
        
        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $task->setState($this->getTaskService()->getQueuedState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertReturnCode(3, array(
            'id' => $taskIds[0]
        ));  
        
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $taskIds[0]
            )
        ));         
    } 
    
    public function testAssignRaisesHttpServerErrorWithOnlyOneWorker() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');

        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);
        
        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $task->setState($this->getTaskService()->getQueuedState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertReturnCode(3, array(
            'id' => $taskIds[0]
        ));  
        
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\TaskAssignJob',
            'task-assign',
            array(
                'id' => $taskIds[0]
            )
        ));         
    } 
    
    public function testAssignTaskWithInProgressEquivalentDoesNotAssignAndInsteadMarksAsInProgress() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');        
        $canonicalUrl = 'http://example.com/'; 
        
        $jobIds = array();
        
        $jobIds[] = $this->getJobIdFromUrl(
            $this->createJob(
                $canonicalUrl,
                null,
                'full site',
                array(
                    'CSS validation'
                ),
                array(
                    'CSS validation' => array(
                        'ignore-warnings' => 1,
                        'ignore-common-cdns' => 1,
                        'vendor-extensions' => 'warn'
                    )
                )
             )->getTargetUrl()
        );
        
        $jobIds[] = $this->getJobIdFromUrl(
            $this->createJob(
                $canonicalUrl,
                null,
                'full site',
                array(
                    'HTML validation',
                    'CSS validation'
                ),
                array(
                    'CSS validation' => array(
                        'ignore-warnings' => 1,
                        'ignore-common-cdns' => 1,
                        'vendor-extensions' => 'warn'
                    )
                )
             )->getTargetUrl()
        );     
        
        $taskIds = array();
        
        foreach ($jobIds as $job_id) {
             $this->prepareJob($canonicalUrl, $job_id);
             $taskIds[$job_id] = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());
        }
        
        $tasks = array(
            $this->getTaskService()->getById($taskIds[$jobIds[0]][0]),
            $this->getTaskService()->getById($taskIds[$jobIds[1]][1])
        );
        
        $this->assertReturnCode(0, array(
            'id' => $tasks[0]->getId()
        ));          

        $this->assertReturnCode(1, array(
            'id' => $tasks[0]->getId()
        ));       
        
        foreach ($tasks as $task) {
            $this->assertEquals('task-in-progress', $task->getState());
        }       
    }
    
    
    public function testAssignFirstTaskOfJobDoesNotBreakRemainingTaskUrls() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker();
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl, null, 'full site', array('Link integrity'))->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        
        $this->assertReturnCode(0, array(
            'id' => $taskIds[0]
        ));         
        
        $job = $this->getJobService()->getById($job_id);
        $tasks = $job->getTasks();
        
        $taskUrls = array();
        foreach ($tasks as $task) {
            $taskUrls[] = $task->getUrl();
        } 
        
        $this->assertEquals(array(
            'http://example.com/',
            'http://example.com/articles/',
            'http://example.com/articles/i-make-the-internet/'
        ), $taskUrls);    
    }
    
}
