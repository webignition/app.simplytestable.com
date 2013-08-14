<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer\ProcessTaskResults\AccountPlanConstraint;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class PublicPlanTest extends BaseSimplyTestableTestCase {
    
    public function testWithConstraintHitOnFirstResultSet() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->create($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        ));
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($job->getUser());

        $numberOfUrlsToDiscover = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit() * 2;
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($canonicalUrl, $numberOfUrlsToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $this->assertEquals($userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit(), count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)));
    }  
    
    
    public function testWithConstraintHitOnSecondResultSet() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->create($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        ));
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($job->getUser());

        $numberOfUrlsToDiscover = (int)round($userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit() / 2);
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($canonicalUrl, $numberOfUrlsToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());        
        $task = $this->getTaskService()->getById($taskIds[1]);
        
        $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        ));        
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($canonicalUrl, $numberOfUrlsToDiscover, $numberOfUrlsToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());        
        
        $this->assertEquals($userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit(), count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)));
    }
    
    
    public function testCrawlJobHasAmmendmentAddedIfDiscoveredUrlSetIsConstrainedByAccountPlan() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $this->createWorker('http://hydrogen.worker.simplytestable.com');
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->create($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $taskIds = $this->getTaskService()->getEntityRepository()->getIdsByJob($crawlJobContainer->getCrawlJob());
        
        $task = $this->getTaskService()->getById($taskIds[0]);
        
        $this->runConsole('simplytestable:task:assign', array(
            $task->getId() =>  true
        ));
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($job->getUser());

        $numberOfUrlsToDiscover = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit() * 2;
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet($canonicalUrl, $numberOfUrlsToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $this->assertEquals(1, $crawlJobContainer->getCrawlJob()->getAmmendments()->count());
        
        
        /* @var $ammendment \SimplyTestable\ApiBundle\Entity\Job\Ammendment */
        $ammendment = $crawlJobContainer->getCrawlJob()->getAmmendments()->first();
        $this->assertEquals('plan-url-limit-reached:discovered-url-count-21', $ammendment->getReason());
    }    

}
