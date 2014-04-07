<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\CancelAction;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

class CancelTest extends BaseControllerJsonTestCase { 
    
    public function testCancelAction() {        
        $canonicalUrl = 'http://example.com';        
        $jobId = $this->createJobAndGetId($canonicalUrl);
        
        $preCancelStatus = json_decode($this->getJobStatus($canonicalUrl, $jobId)->getContent())->state;
        $this->assertEquals('new', $preCancelStatus);
        
        $cancelResponse = $this->getJobController('cancelAction')->cancelAction($canonicalUrl, $jobId);
        $this->assertEquals(200, $cancelResponse->getStatusCode());
        
        $postCancelStatus = json_decode($this->getJobStatus($canonicalUrl, $jobId)->getContent())->state;
        $this->assertEquals('cancelled', $postCancelStatus);        
    }
    
    
    public function testCancelActionInMaintenanceReadOnlyModeReturns503() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertEquals(503, $this->getJobController('cancelAction')->cancelAction('http://example.com', 1)->getStatusCode());        
    }
    
    
    public function testCancelActionInMaintenanceBackupReadOnlyModeReturns503() {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertEquals(503, $this->getJobController('cancelAction')->cancelAction('http://example.com', 1)->getStatusCode());        
    }
    
    
    public function testCancelParentJobCancelsParentJobAndCrawlJob() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, $user->getEmail()));
        
        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);            
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
        
        $this->getJobController('cancelAction', array(
            'user' => $user->getEmail()
        ))->cancelAction($canonicalUrl, $crawlJobContainer->getParentJob()->getId());
        
        $this->assertTrue($crawlJobContainer->getParentJob()->getState()->equals($this->getJobService()->getCancelledState()));
        $this->assertTrue($crawlJobContainer->getCrawlJob()->getState()->equals($this->getJobService()->getCancelledState()));       
    }
    
    public function testCancelCrawlJobRestartsParentJob() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        
        $canonicalUrl = 'http://example.com';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, $user->getEmail()));
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);            
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);        
        
        $crawlTask = $crawlJobContainer->getCrawlJob()->getTasks()->first();      
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/","http:\/\/example.com\/two\/","http:\/\/example.com\/three\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$crawlTask->getUrl(), $crawlTask->getType()->getName(), $crawlTask->getParametersHash());         

        $this->getJobController('cancelAction', array(
            'user' => $user->getEmail()
        ))->cancelAction($canonicalUrl, $crawlJobContainer->getCrawlJob()->getId());
        
        $this->assertTrue($crawlJobContainer->getParentJob()->getState()->equals($this->getJobService()->getQueuedState()));
        $this->assertTrue($crawlJobContainer->getCrawlJob()->getState()->equals($this->getJobService()->getCancelledState()));                 
    }    
    
    public function testCancelRestartsParentWithPredefinedDomainsToIgnoreForCssValidation() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        
        $canonicalUrl = 'http://example.com';
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, $user->getEmail(), 'full site', array(
            'CSS validation'
        ), array(
            'CSS validation' => array(
                'ignore-common-cdns' => 1
            )            
        )));
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);            
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);        
        
        $crawlTask = $crawlJobContainer->getCrawlJob()->getTasks()->first();      
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/","http:\/\/example.com\/two\/","http:\/\/example.com\/three\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$crawlTask->getUrl(), $crawlTask->getType()->getName(), $crawlTask->getParametersHash());         

        $this->getJobController('cancelAction', array(
            'user' => $user->getEmail()
        ))->cancelAction($canonicalUrl, $crawlJobContainer->getCrawlJob()->getId());        
        
        /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
        $task = $job->getTasks()->first();
        $parametersObject = json_decode($task->getParameters());
        
        $this->assertTrue(isset($parametersObject->{'domains-to-ignore'}));
        $this->assertEquals($this->container->getParameter('css-validation-domains-to-ignore'), $parametersObject->{'domains-to-ignore'});
    }
    
    
    public function testCancelRestartsParentWithPredefinedDomainsToIgnoreForJsStaticAnalysis() {
        $user = $this->createAndActivateUser('user@example.com', 'password');
        
        $canonicalUrl = 'http://example.com';
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, $user->getEmail(), 'full site', array(
            'JS static analysis'
        ), array(
            'JS static analysis' => array(
                'ignore-common-cdns' => 1
            )            
        )));
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);
        
        $this->assertFalse($this->getCrawlJobContainerService()->hasForJob($job));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);            
        
        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);        
        
        $crawlTask = $crawlJobContainer->getCrawlJob()->getTasks()->first();      
        
        $this->getTaskController('completeByUrlAndTaskTypeAction', array(
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => '["http:\/\/example.com\/one\/","http:\/\/example.com\/two\/","http:\/\/example.com\/three\/"]',
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ))->completeByUrlAndTaskTypeAction((string)$crawlTask->getUrl(), $crawlTask->getType()->getName(), $crawlTask->getParametersHash());         

        $this->getJobController('cancelAction', array(
            'user' => $user->getEmail()
        ))->cancelAction($canonicalUrl, $crawlJobContainer->getCrawlJob()->getId());        
        
        /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
        $task = $job->getTasks()->first();
        $parametersObject = json_decode($task->getParameters());
        
        $this->assertTrue(isset($parametersObject->{'domains-to-ignore'}));
        $this->assertEquals($this->container->getParameter('js-static-analysis-domains-to-ignore'), $parametersObject->{'domains-to-ignore'});
    }    
    
}


