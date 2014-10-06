<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer\ProcessTaskResults\AccountPlanConstraint;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class PublicPlanTest extends BaseSimplyTestableTestCase {
    
    public function testWithConstraintHitOnFirstResultSet() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($job->getUser());
        $numberOfUrlsToDiscover = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit() * 2;
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, $numberOfUrlsToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $this->assertEquals($userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit(), count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)));
    }  
    
    
    public function testWithConstraintHitOnSecondResultSet() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($job->getUser());
        $numberOfUrlsToDiscover = (int)round($userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit() / 2);
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, $numberOfUrlsToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());
        
        $task = $crawlJobContainer->getCrawlJob()->getTasks()->get(1);
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));        
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, $numberOfUrlsToDiscover, $numberOfUrlsToDiscover)),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$task->getUrl(), $task->getType()->getName(), $task->getParametersHash());        
        
        $this->assertEquals($userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit(), count($this->getCrawlJobContainerService()->getDiscoveredUrls($crawlJobContainer, true)));
    }
    
    
    public function testCrawlJobHasAmmendmentAddedIfDiscoveredUrlSetIsConstrainedByAccountPlan() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses')));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);        
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $task->getId()
        ));
        
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($job->getUser());

        $numberOfUrlsToDiscover = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit() * 2;
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(self::DEFAULT_CANONICAL_URL, $numberOfUrlsToDiscover)),
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
