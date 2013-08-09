<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteByUrlAndTaskTypeAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CompleteByUrlAndTaskTypeActionTest extends BaseControllerJsonTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }   

    public function testWithSingleMatchingTask() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $this->prepareJob($canonicalUrl, $job_id);

        $taskIds = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        )));
        
        $job = json_decode($this->fetchJob($canonicalUrl, $job_id)->getContent());        
        $this->assertEquals('in-progress', $job->state);
        
        $response = $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '[]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction($canonicalUrl, $task->getType()->getName(), $task->getParametersHash());
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('task-completed', $task->getState()->getName());
    }
    
    public function testWithMultipleMatchingTasksForSameUser() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->createWorker('http://hydrogen.worker.simplytestable.com');        
        $canonicalUrl = 'http://example.com/'; 
        
        $jobIds = array();
        
        $jobIds[] = $this->getJobIdFromUrl(
            $this->createJob($canonicalUrl, null, 'full site', array('HTML validation'))->getTargetUrl()
        );
        
        $jobIds[] = $this->getJobIdFromUrl(
            $this->createJob($canonicalUrl, null, 'full site', array('HTML validation', 'CSS validation'))->getTargetUrl()
        );
        
        $taskIds = array();
        
        foreach ($jobIds as $job_id) {
             $this->prepareJob($canonicalUrl, $job_id);
             $taskIds[$job_id] = json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());
        }
        
        $task = $this->getTaskService()->getById($taskIds[$jobIds[0]][0]);        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        )));
        
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
            $this->assertEquals($canonicalUrl, (string)$task->getUrl());
            $this->assertEquals('task-completed', $task->getState()->getName());
        }
    }  
    
    public function testWithMultipleMatchingTasksForDifferentUsers() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $user1 = $this->createAndActivateUser('user1@example.com', 'password1');
        $user2 = $this->createAndActivateUser('user2@example.com', 'password1');

        $this->createWorker('http://hydrogen.worker.simplytestable.com');        
        $canonicalUrl = 'http://example.com/'; 
        
        $jobIds = array();
        
        $jobIds[] = $this->getJobIdFromUrl(
            $this->createJob($canonicalUrl, $user1->getEmail(), 'full site', array('HTML validation'))->getTargetUrl()
        );
        
        $jobIds[] = $this->getJobIdFromUrl(
            $this->createJob($canonicalUrl, $user2->getEmail(), 'full site', array('HTML validation', 'CSS validation'))->getTargetUrl()
        );
        
        $taskIds = array();
        
        foreach ($jobIds as $index => $job_id) {
             $this->prepareJob($canonicalUrl, $job_id);
             $userEmail = ($index === 0) ? $user1->getEmail() : $user2->getEmail();
             $taskIds[$job_id] = json_decode($this->getJobController('taskIdsAction', array('user' => $userEmail))->taskIdsAction($canonicalUrl, $job_id)->getContent());
        }
        
        $task = $this->getTaskService()->getById($taskIds[$jobIds[0]][0]);        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        )));
        
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
            $this->assertEquals($canonicalUrl, (string)$task->getUrl());
            $this->assertEquals('task-completed', $task->getState()->getName());
        }
    }  
    
    public function testWithSingleMatchingTaskFromMultiplePossibleTasksByParameters() {
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
                        'ignore-warnings' => 0,
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
        
        $task = $this->getTaskService()->getById($taskIds[$jobIds[0]][0]);        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        )));
        
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
            $this->assertEquals($canonicalUrl, (string)$task->getUrl());
            $this->assertEquals(($taskIndex ===0 ? 'task-completed' : 'task-queued'), $task->getState()->getName());
        }        
    }
    
    public function testWithMultipleMatchingTaskFromMultiplePossibleTasksByParameters() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $users = $this->createAndActivateUserCollection(3);

        $this->createWorker('http://hydrogen.worker.simplytestable.com');        
        $canonicalUrl = 'http://example.com/'; 
        
        $jobIds = array();

        
        $jobPropertyCollection = array();        
        
        $jobPropertyCollection[] = array(
            'test-types' => array(
                'CSS validation'
            ),
            'test-type-options' => array(
                'CSS validation' => array(
                    'ignore-warnings' => 1,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
                )                
            )
        );
        
        $jobPropertyCollection[] = array(
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
            )
        );
        
        foreach ($users as $user) {
            $jobIds[$user->getEmail()] = array();
            
            foreach ($jobPropertyCollection as $jobProperties) {
                $jobIds[$user->getEmail()][] = $this->getJobIdFromUrl(
                    $this->createJob(
                        $canonicalUrl,
                        $user->getEmail(),
                        'full site',
                        $jobProperties['test-types'],
                        $jobProperties['test-type-options']
                     )->getTargetUrl()
                );                
            }
        }
        
        $taskIds = array();
        foreach ($jobIds as $userEmail => $jobIdSet) {
            foreach ($jobIdSet as $job_id) {                
                $this->prepareJob($canonicalUrl, $job_id);              
                $taskIds[$job_id] = json_decode($this->getJobController('taskIdsAction', array('user' => $userEmail))->taskIdsAction($canonicalUrl, $job_id)->getContent());                
            }
        }        
        
        $task = $this->getTaskService()->getById($taskIds[$jobIds[$users[0]->getEmail()][0]][0]);        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        )));
        
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
            $this->assertEquals($canonicalUrl, (string)$task->getUrl());
            
            if ($taskIndex % 2) {
                $this->assertEquals('task-queued', $task->getState()->getName());
            } else {
                $this->assertEquals('task-completed', $task->getState()->getName());
            }
        }       
    }    
    
}


