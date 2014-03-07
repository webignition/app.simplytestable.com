<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TaskPreProcessor;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class LinkIntegrityPreProcessorTest extends BaseSimplyTestableTestCase {
    
    public function testWithCurlErrorRetrievingTestContent() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity')));        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));
        
        $taskOutputContent = array(
            array(
                'context' => '<a href="http://example.com/one">Example One</a>',
                'state' => 200,
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
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/three'
            )            
        );

        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->createWorker();
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode($taskOutputContent),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $tasks[1]->getId()
        ));        
        
        $this->assertEquals('task-in-progress', $tasks[1]->getState()->getName());     
    }
    
    public function testWithHttpErrorRetrievingTestContent() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity')));        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));
        
        $taskOutputContent = array(
            array(
                'context' => '<a href="http://example.com/one">Example One</a>',
                'state' => 200,
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
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/three'
            )            
        );

        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->createWorker();
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode($taskOutputContent),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
     
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $tasks[1]->getId()
        ));
        
        $this->assertEquals('task-in-progress', $tasks[1]->getState()->getName());     
    }    
    
    public function testDetermineOutputFromPriorRecentTests() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity')));        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));        
        
        $taskOutputContent = array(
            array(
                'context' => '<a href="http://example.com/one">Example One</a>',
                'state' => 200,
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
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/three'
            )            
        );

        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode($taskOutputContent),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
     
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $tasks[1]->getId()
        ));
        
        $this->assertEquals(array(
            array(
                'context' => '<a href="http://example.com/three">Another Example Three</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/three'
            ),            
            array(
                'context' => '<a href="http://example.com/one">Another Example One</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/one'
            ),
            array(
                'context' => '<a href="http://example.com/two">Another Example Two</a>',
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/two'
            )           
        ), json_decode($tasks[1]->getOutput()->getOutput(), true));
    }    

    public function testDetermineCorrectErrorCount() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity')));        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));        
        
        $taskOutputContent = array(
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
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/three'
            )            
        );

        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode($taskOutputContent),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $tasks[1]->getId()
        ));
        
        $this->assertEquals(0, $tasks[1]->getOutput()->getErrorCount());
    }
    
    public function testWithAssignSelected() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity')));        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));
        
        $taskOutputContent = array(
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
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/three'
            )            
        );

        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode($taskOutputContent),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
        
        /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
        $task = $tasks[1];
        
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->createWorker();
        
        $this->assertEquals(0, $this->executeCommand('simplytestable:task:assign-selected'));        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
    }  
    
    public function testWithAssignCollection() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity')));        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));
        
        $taskOutputContent = array(
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
                'state' => 200,
                'type' => 'http',
                'url' => 'http://example.com/three'
            )            
        );

        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode($taskOutputContent),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
        
        /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
        $task = $tasks[1];

        $this->createWorker();
        $this->assertEquals(0, $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $task->getId()
        )));
        
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
    }  
    
    public function testPreprocessingUsesCorrectHistoricTaskType() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('CSS validation', 'Link integrity')));        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));

        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => '[]',            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
        
        $this->createWorker();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $tasks[1]->getId()
        ));
        
        $this->assertEquals('task-in-progress', $tasks[1]->getState()->getName());
    }
    
    public function testStorePartialTaskOutputBeforeAssign() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity')));        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));

        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
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
                    'state' => 200,
                    'type' => 'http',
                    'url' => 'http://example.com/three'
                )            
            )),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $tasks[1]->getId()
        ));
        
        $this->assertTrue($tasks[1]->hasOutput());
    }
    
    
    public function testSetExcludedUrlsParameterWhenStoringPartialOutput() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity')));        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));

        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
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
                    'state' => 200,
                    'type' => 'http',
                    'url' => 'http://example.com/three'
                )            
            )),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $tasks[1]->getId()
        ));
        
        $this->assertTrue($tasks[1]->hasOutput());        
        $this->assertEquals(array(
            'excluded-urls' => array(
                'http://example.com/three',
                'http://example.com/two'
                
            )
        ), json_decode($tasks[1]->getParameters(), true));        
    }     
    
    
    public function testSetExcludedUrlsWhenExcludedDomainsParameterExists() {
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity'), array('Link integrity' => array(
            'excluded-domains' => array(
                'instagram.com'
            )
        )))); 
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));

        $tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
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
                    'state' => 200,
                    'type' => 'http',
                    'url' => 'http://example.com/three'
                )            
            )),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[0]->getUrl(), $tasks[0]->getType()->getName(), $tasks[0]->getParametersHash());
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $tasks[1]->getId()
        ));
        
        $this->assertTrue($tasks[1]->hasOutput());        
        $this->assertEquals(array(
            'excluded-urls' => array(
                'http://example.com/three',
                'http://example.com/two'                
            ),
            'excluded-domains' => array(
                'instagram.com'
            )
        ), json_decode($tasks[1]->getParameters(), true));        
    }    
    
}
