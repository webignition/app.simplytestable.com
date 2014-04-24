<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteByUrlAndTaskTypeAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class DefaultTest extends BaseControllerJsonTestCase {
 
    public function testWithSingleMatchingTask() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->queueTaskAssignResponseHttpFixture();
        
        $this->createWorker();

        $task = $job->getTasks()->first();
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        )); 
        
        $this->assertEquals($this->getJobService()->getInProgressState(), $job->getState());
        
        $response = $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction($task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->getTaskService()->getCompletedState(), $task->getState());
    }
    
    public function testWithMultipleMatchingTasksForSameUser() {
        $this->setJobTypeConstraintLimits();
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(
            self::DEFAULT_CANONICAL_URL,
            null,
            'full site',
            array('HTML validation')
        ));
        
        $this->getJobService()->getById($this->createResolveAndPrepareJob(
            self::DEFAULT_CANONICAL_URL,
            null,
            'full site',
            array('HTML validation', 'CSS validation')
        ));   
        
        $this->queueTaskAssignResponseHttpFixture();
        $this->createWorker();          
        
        $task = $job->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $response = $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());                
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $tasks = $this->getTaskService()->getEntityRepository()->findBy(array(
            'url' => (string)$task->getUrl(),
            'type' => $this->getTaskTypeService()->getByName('HTML validation')
        ));
        
        $this->assertEquals(2, count($tasks));        
        
        foreach ($tasks as $task) {
            $this->assertEquals('http://example.com/0/', (string)$task->getUrl());
            $this->assertEquals($this->getTaskService()->getCompletedState(), $task->getState());
        }
    }  
    
    public function testWithMultipleMatchingTasksForDifferentUsers() {
        $user1 = $this->createAndActivateUser('user1@example.com', 'password1');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password1');        
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(
            self::DEFAULT_CANONICAL_URL,
            $user1->getEmail(),
            'full site',
            array('HTML validation')
        ));
        
        $this->getJobService()->getById($this->createResolveAndPrepareJob(
            self::DEFAULT_CANONICAL_URL,
            $user2->getEmail(),
            'full site',
            array('HTML validation', 'CSS validation')
        ));   
        
        
        $this->queueTaskAssignResponseHttpFixture();
        $this->createWorker();
        
        $task = $job->getTasks()->first();
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $response = $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());        
        
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $tasks = $this->getTaskService()->getEntityRepository()->findBy(array(
            'url' => (string)$task->getUrl(),
            'type' => $this->getTaskTypeService()->getByName('HTML validation')
        ));
        
        $this->assertEquals(2, count($tasks));        
        
        foreach ($tasks as $task) {
            $this->assertEquals('http://example.com/0/', (string)$task->getUrl());
            $this->assertEquals($this->getTaskService()->getCompletedState(), $task->getState());
        }
    }  
    
    public function testWithSingleMatchingTaskFromMultiplePossibleTasksByParameters() {
        $this->setJobTypeConstraintLimits();
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(
            self::DEFAULT_CANONICAL_URL,
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
        ));
        
        $this->getJobService()->getById($this->createResolveAndPrepareJob(
            self::DEFAULT_CANONICAL_URL,
            null,
            'full site',
            array(
                'HTML validation',
                'CSS validation'
            ),
            array(
                'CSS validation' => array(
                    'ignore-warnings' => 0,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
                )
            )
        ));
        
        $this->queueTaskAssignResponseHttpFixture();
        $this->createWorker();  
    
        $task = $job->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $response = $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());        

        $this->assertEquals(200, $response->getStatusCode());
        
        $tasks = $this->getTaskService()->getEntityRepository()->findBy(array(
            'url' => (string)$task->getUrl(),
            'type' => $this->getTaskTypeService()->getByName('CSS validation')
        ));
        
        $this->assertEquals(2, count($tasks));        
        
        foreach ($tasks as $taskIndex => $task) {
            $this->assertEquals('http://example.com/0/', (string)$task->getUrl());
            $this->assertEquals(($taskIndex === 0 ? $this->getTaskService()->getCompletedState() : $this->getTaskService()->getQueuedState()), $task->getState());
        }        
    }
    
    public function testWithMultipleMatchingTaskFromMultiplePossibleTasksByParameters() {
        $users = $this->createAndActivateUserCollection(3);
        $this->createWorker();
        
        $this->queueTaskAssignResponseHttpFixture();
        
        $jobPropertyCollection = array(
            array(
            'test-types' => array(
                'CSS validation'
            ),
            'test-type-options' => array(
                'CSS validation' => array(
                    'ignore-warnings' => 1,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
                )                
            )),
            array(
            'test-types' => array(
                    'HTML validation',
                    'CSS validation'
            ),
            'test-type-options' => array(
                'CSS validation' => array(
                    'ignore-warnings' => 0,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
                )              
            ))
        );
        
        $jobs = array();
        
        foreach ($users as $user) {
            $jobs[$user->getEmail()] = array();
            
            foreach ($jobPropertyCollection as $jobProperties) {
                $jobs[$user->getEmail()][] = $this->getJobService()->getById($this->createResolveAndPrepareJob(
                        self::DEFAULT_CANONICAL_URL,
                        $user->getEmail(),
                        'full site',
                        $jobProperties['test-types'],
                        $jobProperties['test-type-options']
                 ));               
            }
        }
    
        $task = $jobs[$users[0]->getEmail()][0]->getTasks()->first();
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $response = $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());                
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $tasks = $this->getTaskService()->getEntityRepository()->findBy(array(
            'url' => (string)$task->getUrl(),
            'type' => $this->getTaskTypeService()->getByName('CSS validation')
        ));
      
        foreach ($tasks as $taskIndex => $task) {            
            $this->assertEquals('http://example.com/0/', (string)$task->getUrl());
            
            if ($taskIndex % 2) {
                $this->assertEquals($this->getTaskService()->getQueuedState(), $task->getState());
            } else {
                $this->assertEquals($this->getTaskService()->getCompletedState(), $task->getState());
            }
        }       
    } 
    
    public function testWithNoMatchingTaskFromMultiplePossibleTasksByParameters() {
        $this->setJobTypeConstraintLimits();
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(
            self::DEFAULT_CANONICAL_URL,
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
        ));
        
        $this->getJobService()->getById($this->createResolveAndPrepareJob(
            self::DEFAULT_CANONICAL_URL,
            null,
            'full site',
            array(
                'HTML validation',
                'CSS validation'
            ),
            array(
                'CSS validation' => array(
                    'ignore-warnings' => 0,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
                )
            )
        ));        
        
        $this->queueTaskAssignResponseHttpFixture();
        $this->createWorker();
        
        $task = $job->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $response = $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), 'invalid-parameter-hash');        

        $this->assertEquals(410, $response->getStatusCode());
        
        $tasks = $this->getTaskService()->getEntityRepository()->findBy(array(
            'url' => (string)$task->getUrl(),
            'type' => $this->getTaskTypeService()->getByName('CSS validation')
        ));
        
        $this->assertEquals(2, count($tasks));        
        
        foreach ($tasks as $taskIndex => $task) {
            $this->assertEquals('http://example.com/0/', (string)$task->getUrl());
            $this->assertEquals(($taskIndex === 0 ? $this->getTaskService()->getInProgressState() : $this->getTaskService()->getQueuedState()), $task->getState());
        }        
    }  
    
    
    private function setJobTypeConstraintLimits() {
        $this->getJobUserAccountPlanEnforcementService()->setUser($this->getUserService()->getPublicUser());
        
        $fullSiteJobsPerSiteConstraint = $this->getJobUserAccountPlanEnforcementService()->getFullSiteJobLimitConstraint();
        $singleUrlJobsPerUrlConstraint = $this->getJobUserAccountPlanEnforcementService()->getSingleUrlJobLimitConstraint();
        
        $fullSiteJobsPerSiteConstraint->setLimit(2);
        $singleUrlJobsPerUrlConstraint->setLimit(2);
        
        $this->getJobService()->getEntityManager()->persist($fullSiteJobsPerSiteConstraint);
        $this->getJobService()->getEntityManager()->persist($singleUrlJobsPerUrlConstraint);          
    }      
    
}


