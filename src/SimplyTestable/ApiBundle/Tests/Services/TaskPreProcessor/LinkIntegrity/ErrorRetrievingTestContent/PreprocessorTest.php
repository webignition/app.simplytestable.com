<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TaskPreProcessor\LinkIntegrity\ErrorRetrievingTestContent;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class PreprocessorTest extends BaseSimplyTestableTestCase {
    
    private $taskOutputContent = array(
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
    
    
    /**
     *
     * @var \Doctrine\ORM\PersistentCollection 
     */
    private $tasks = null;
    
    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity')));        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));

        $this->tasks = $job->getTasks();
        
        $task = $this->tasks->first();

        $now = new \DateTime();
        
        $this->createWorker();
        $this->getTaskController('completeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode($this->taskOutputContent),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeAction((string) $task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
                
        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $this->tasks->get(1)->getId()
        ));         
    }
    
    public function testOnethTaskIsInProgressAfterAssigning() {
        $this->assertEquals($this->getTaskService()->getInProgressState(), $this->tasks->get(1)->getState()); 
    }
    
}
