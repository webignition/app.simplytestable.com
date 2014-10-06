<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TaskPreProcessor\LinkIntegrity;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class PreProcessorTest extends BaseSimplyTestableTestCase {
    
    /**
     *
     * @var \Doctrine\ORM\PersistentCollection 
     */
    protected $tasks = null;
    
    
    /**
     * @return array
     */
    abstract protected function getCompletedTaskOutput();
    
    
    /**
     * 
     * @return array
     */
    protected function getTestTypeOptions() {
        return array();
    }
    
    
    /**
     * 
     * @return array
     */
    protected function getJobParameters() {
        return array();
    }    
    
    
    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site', array('Link integrity'), $this->getTestTypeOptions(), $this->getJobParameters()));        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));        

        $this->tasks = $job->getTasks();

        $now = new \DateTime();
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode($this->getCompletedTaskOutput()),            
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $this->tasks->first()->getUrl(), $this->tasks->first()->getType()->getName(), $this->tasks->first()->getParametersHash());
    }
    
    
    protected function getDefaultCompletedTaskOutput() {
        return array(
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
    }    

    
}
