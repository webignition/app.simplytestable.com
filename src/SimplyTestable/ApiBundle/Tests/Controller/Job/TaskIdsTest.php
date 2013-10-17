<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job;

class TaskIdsTest extends AbstractAccessTest {
    
    protected function getActionName() {
        return 'taskIdsAction';
    }
    
    public function testTaskIdsAction() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__) . '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';       
        $job_id = $this->getJobIdFromUrl($this->createJob($canonicalUrl)->getTargetUrl());
        
        $job = $this->prepareJob($canonicalUrl, $job_id);
        
        $response = $this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id);
        $taskIds = json_decode($response->getContent());
        
        $expectedTaskIdCount = $job->url_count * count($job->task_types);
        
        $this->assertEquals($expectedTaskIdCount, count($taskIds));
        
        foreach ($taskIds as $taskId) {
            $this->assertInternalType('integer', $taskId);
            $this->assertGreaterThan(0, $taskId);
        }    
    }
    
}