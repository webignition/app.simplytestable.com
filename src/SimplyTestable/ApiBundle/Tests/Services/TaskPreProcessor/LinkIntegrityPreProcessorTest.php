<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TaskPreProcessor;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class LinkIntegrityPreProcessorTest extends BaseSimplyTestableTestCase {

    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
    
    
    // test with curl error, http error retrieving content to test
    // test get from past tests
    // test get is in last X minutes
    // test get is for correct task type    
    // test get error count right
    
    public function testDetermineOutputFromPriorRecentTests() {
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

        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, null, 'full site', array('Link integrity')));

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
     
        $this->assertEquals(0, $this->runConsole('simplytestable:task:assign', array(
            $tasks[1]->getId() =>  true
        )));
        
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

        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, null, 'full site', array('Link integrity')));

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
        
        $this->runConsole('simplytestable:task:assign', array(
            $tasks[1]->getId() =>  true
        ));
        
        $this->assertEquals(1, $tasks[1]->getOutput()->getErrorCount());
    }

}
