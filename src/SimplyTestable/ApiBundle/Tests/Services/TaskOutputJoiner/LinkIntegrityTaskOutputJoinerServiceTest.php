<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TaskOutputJoiner;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class LinkIntegrityTaskOutputJoinerServiceTest extends BaseSimplyTestableTestCase {

    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();
    }
   
    public function testJoinOnComplete() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));

        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl, null, 'full site', array('Link integrity')));

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
        
        $this->runConsole('simplytestable:task:assign', array(
            $tasks[1]->getId() =>  true
        ));    
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => $now->format('Y-m-d H:i:s'),
            'output' => json_encode(array(
                array(
                    'context' => '<a href="http://example.com/four">Example Four</a>',
                    'state' => 404,
                    'type' => 'http',
                    'url' => 'http://example.com/four'
                )          
            )),          
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string) $tasks[1]->getUrl(), $tasks[1]->getType()->getName(), $tasks[1]->getParametersHash());        
        
        $this->assertEquals(2, $tasks[1]->getOutput()->getErrorCount());                
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\TaskPreProcessor\FactoryService
     */    
    private function getTaskPreprocessorFactoryService() {
        return $this->container->get('simplytestable.services.TaskPreProcessorServiceFactory');
    }     
    
}
