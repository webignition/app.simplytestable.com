<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Tests\BaseTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Job;

class JobPrepareCommandTest extends BaseTestCase {    
    
    const TESTS_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\TestsController';
    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Controller\TestsController
     */
    private $testsController = null;

    public function testPrepareNewJob() {        
        $this->setupDatabase();
        
        $job = $this->createJob('http://example.com');        
        $response = json_decode($job->getContent());
        $job_id = $response->id;
        
        $this->assertEquals(1, $job_id);
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true,
            $this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses' => true
        ));
        
        $this->getJobService()->getEntityRepository()->clear();
        
        $job = $this->fetchJob('http://example.com', $job_id);
        $response = json_decode($job->getContent());
        
        $expectedUrls = array(
            'http://example.com/',
            'http://example.com/articles/',
            'http://example.com/articles/i-make-the-internet/'
        );
        
        $this->assertEquals('queued', $response->state);
        $this->assertEquals(3, count($response->tasks));
        
        foreach ($response->tasks as $taskIndex => $task) {
            $this->assertEquals($taskIndex + 1, $task->id);
            $this->assertEquals('queued', $task->state);
            $this->assertEquals($expectedUrls[$taskIndex], $task->url);
        }
    }
    
    
    /**
     *
     * @param string $canonicalUrl
     * @return Job
     */
    private function createJob($canonicalUrl) {
        return $this->getTestsController('startAction')->startAction($canonicalUrl);
    }
    
    
    /**
     *
     * @param string $canonicalUrl
     * @param int $id
     * @return Job
     */
    private function fetchJob($canonicalUrl, $id) {        
        return $this->getTestsController('statusAction')->statusAction($canonicalUrl, $id);    
    }
    
    
    /**
     *
     * @param string $methodName
     * @return SimplyTestable\ApiBundle\Controller\TestsController
     */
    private function getTestsController($methodName) {
        if (is_null($this->testsController)) {
            $this->testsController = $this->createController(self::TESTS_CONTROLLER_NAME, $methodName);
        }        
        
        return $this->testsController;
    }
    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\JobService
     */
    private function getJobService() {
        return $this->container->get('simplytestable.services.jobservice');
    }

}
