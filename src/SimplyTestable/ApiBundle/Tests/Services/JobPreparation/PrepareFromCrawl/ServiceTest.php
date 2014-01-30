<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\PrepareFromCrawl;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase {    
    
    const EXPECTED_TASK_TYPE_COUNT = 4;
    const CANONICAL_URL = 'http://example.com';
    
    public function setUp() {
        parent::setUp(); 
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404'
        )));
    }
    
    public function testCrawlJobAmmendmentsArePassedToParentJob() {        
        $job = $this->getJobService()->getById($this->createAndPrepareJob(self::CANONICAL_URL, $this->getTestUser()->getEmail()));     
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);        
        $urlDiscoveryTask = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        
        $urlLimit = $this->getUserAccountPlanService()->getForUser($this->getTestUser())->getPlan()->getConstraintNamed('urls_per_job')->getLimit();

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::CANONICAL_URL, $urlLimit)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$urlDiscoveryTask->getUrl(), $urlDiscoveryTask->getType()->getName(), $urlDiscoveryTask->getParametersHash());
        
        $this->assertEquals('plan-url-limit-reached:discovered-url-count-' . ($urlLimit + 1), $job->getAmmendments()->first()->getReason());
    }
    
    
    
}
