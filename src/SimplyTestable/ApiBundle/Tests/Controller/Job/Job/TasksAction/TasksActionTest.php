<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\TasksAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class TasksActionTest extends BaseControllerJsonTestCase {
    
    protected function getActionName() {
        return 'tasksAction';
    }
    
    public function testNoOutputForIncompleteTasksWithPartialOutput() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(
                self::DEFAULT_CANONICAL_URL,
                null,
                'full site',
                array('Link integrity')
         ));
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->getTaskController('completeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode(array(
                array(
                    'context' => '<a href="http://example.com/one">Example One</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/one'
                ),
                array(
                    'context' => '<a href="http://example.com/two">Example Two</a>',
                    'state' => 200,
                    'type' => 'http',
                    'url' => 'http://example.com/two'
                ),
                array(
                    'context' => '<a href="http://example.com/three">Example Three</a>',
                    'state' => 204,
                    'type' => 'http',
                    'url' => 'http://example.com/three'
                )            
            )),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 1,
            'warningCount' => 0
        ))->completeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $tasks[1]->getId()
        ));        
        
        $tasksResponseObject = json_decode($this->getJobController('tasksAction')->tasksAction($job->getWebsite()->getCanonicalUrl(), $job->getId())->getContent());
        
        foreach ($tasksResponseObject as $taskResponse) {            
            if ($taskResponse->id == $tasks[0]->getId()) {
                $this->assertTrue(isset($taskResponse->output));
            } else {
                $this->assertFalse(isset($taskResponse->output));
            }
        }
    }
    
    
    public function testFailedNoRetryAvailableTaskOutputIsReturned() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(
                self::DEFAULT_CANONICAL_URL,
                null,
                'full site',
                array('HTML validation')
        ));
        
        foreach ($job->getTasks() as $task) {
            $this->getTaskController('completeAction', array(
                'end_date_time' => '2012-03-08 17:03:00',
                'output' => '{"messages":[]}',
                'contentType' => 'application/json',
                'state' => 'task-failed-no-retry-available',
                'errorCount' => 1,
                'warningCount' => 0
            ))->completeAction((string) $task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        }
        
        $tasksResponseObject = json_decode($this->getJobController('tasksAction')->tasksAction($job->getWebsite()->getCanonicalUrl(), $job->getId())->getContent());
        
        foreach ($tasksResponseObject as $taskResponse) {
            $this->assertTrue(isset($taskResponse->output));
        }        
    }    
    
}