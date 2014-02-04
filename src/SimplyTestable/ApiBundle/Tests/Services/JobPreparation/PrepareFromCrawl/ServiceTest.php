<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\PrepareFromCrawl;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase {    

    
    public function testCrawlJobAmmendmentsArePassedToParentJob() {        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail()));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);        
        $urlDiscoveryTask = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        
        $urlLimit = $this->getUserAccountPlanService()->getForUser($this->getTestUser())->getPlan()->getConstraintNamed('urls_per_job')->getLimit();

        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, $urlLimit)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$urlDiscoveryTask->getUrl(), $urlDiscoveryTask->getType()->getName(), $urlDiscoveryTask->getParametersHash());
        
        $this->assertEquals('plan-url-limit-reached:discovered-url-count-' . ($urlLimit + 1), $job->getAmmendments()->first()->getReason());
    }
    
    
    
}
