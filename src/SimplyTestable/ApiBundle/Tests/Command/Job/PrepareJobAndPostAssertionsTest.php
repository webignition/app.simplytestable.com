<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class PrepareJobAndPostAssertionsTest extends BaseSimplyTestableTestCase {    
    
    const EXPECTED_TASK_TYPE_COUNT = 3;
    
    public function setUp() {
        parent::setUp();
        self::setupDatabase();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName()). '/HttpResponses'));
    }   
    
    protected function prepareJobAndPostAssertions($canonicalUrl, $expectedUrlCount, $expectedJobEndState, $expectedTaskSetUrls) {
        $expectedTaskCount = self::EXPECTED_TASK_TYPE_COUNT * $expectedUrlCount;        
        
        $jobCreateResponse = $this->createJob($canonicalUrl);        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertInternalType('integer', $job_id);
        $this->assertGreaterThan(0, $job_id);
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        )));
        
        $this->getJobService()->getEntityRepository()->clear();
        
        $jobResponse = json_decode($this->fetchJob($canonicalUrl, $job_id)->getContent());
        
        $this->assertEquals($expectedJobEndState, $jobResponse->state);
        $this->assertEquals(self::EXPECTED_TASK_TYPE_COUNT, count($jobResponse->task_types));
        $this->assertEquals($expectedUrlCount, $jobResponse->url_count);
        $this->assertEquals($expectedTaskCount, $jobResponse->task_count);        
        
        $job = $this->getJobService()->getById($job_id);
        $tasks = $job->getTasks();
        
        $this->assertEquals($expectedTaskCount, $tasks->count());

        for ($subsetOffset = 0; $subsetOffset < $expectedTaskCount; $subsetOffset += 3) {
            $taskSubset = $tasks->slice($subsetOffset, self::EXPECTED_TASK_TYPE_COUNT);
            
            foreach ($taskSubset as $task) {
                $this->assertEquals($expectedTaskSetUrls[$subsetOffset / self::EXPECTED_TASK_TYPE_COUNT], $task->getUrl());
            }
        }        
    }

}
