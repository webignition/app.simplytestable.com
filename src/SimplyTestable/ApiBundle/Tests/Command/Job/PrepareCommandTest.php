<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class PrepareCommandTest extends BaseSimplyTestableTestCase {    
    
    const EXPECTED_TASK_TYPE_COUNT = 3;
    
    public function testPrepareInWrongStateReturnsStatusCode1() {        
        $canonicalUrl = 'http://example.com';        
        $jobId = $this->createJobAndGetId($canonicalUrl);

        $this->assertInternalType('integer', $jobId);
        $this->assertGreaterThan(0, $jobId);
        
        $cancelResponse = $this->getJobController('cancelAction')->cancelAction($canonicalUrl, $jobId);
        $this->assertEquals(200, $cancelResponse->getStatusCode());
        
        $postCancelStatus = json_decode($this->getJobStatus($canonicalUrl, $jobId)->getContent())->state;
        $this->assertEquals('cancelled', $postCancelStatus);         
        
        $this->assertEquals(1, $this->runConsole('simplytestable:job:prepare', array(
            $jobId =>  true
        )));       
    }
    
    
    public function testPrepareInMaintenanceReadOnlyModeReturnsStatusCode2() {     
        $this->assertEquals(0, $this->runConsole('simplytestable:maintenance:enable-read-only'));        
        $this->assertEquals(2, $this->runConsole('simplytestable:job:prepare', array(
            1 =>  true
        )));
    }    

    public function testPrepareNewJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        
        $expectedTaskUrls = array(
            'http://example.com/',
            'http://example.com/articles/',
            'http://example.com/articles/i-make-the-internet/'
        );        
        
        $jobCreateResponse = $this->createJob($canonicalUrl);        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertInternalType('integer', $job_id);
        $this->assertGreaterThan(0, $job_id);
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        )));
        
        $this->getJobService()->getEntityRepository()->clear();
        
        $jobObject = $this->fetchJob($canonicalUrl, $job_id);
        $response = json_decode($jobObject->getContent());
        
        $this->assertEquals('queued', $response->state);
        $this->assertNotNull($response->time_period);
        $this->assertNotNull($response->time_period->start_date_time);
        
        $taskTypeCount = count($response->task_types);
        
        $this->assertEquals(count($expectedTaskUrls), $response->url_count);
        $this->assertEquals(count($expectedTaskUrls) * $taskTypeCount, $response->task_count);
        $this->assertEquals(count($expectedTaskUrls) * $taskTypeCount, $response->task_count_by_state->queued);
        
        $job = $this->getJobService()->getById($job_id);        
        $discoveredTaskUrls = array();
        
        foreach ($job->getTasks() as $task) {
            if (!in_array($task->getUrl(), $discoveredTaskUrls)) {
                $discoveredTaskUrls[] = $task->getUrl();
            } 
        } 
        
        foreach ($discoveredTaskUrls as $taskUrl) {
            $this->assertTrue(in_array($taskUrl, $expectedTaskUrls));
        }
        
        foreach ($expectedTaskUrls as $taskUrl) {
            $this->assertTrue(in_array($taskUrl, $discoveredTaskUrls));
        }        
        
        return;
    }
    
    
    public function testSingleUrlJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        
        $expectedTaskUrls = array(
            'http://example.com/'
        );        
        
        $jobCreateResponse = $this->createJob($canonicalUrl, null, 'single url');        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertInternalType('integer', $job_id);
        $this->assertGreaterThan(0, $job_id);       
        
        $this->assertEquals(1, $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        )));
        
        $this->getJobService()->getEntityRepository()->clear();
        
        $job = $this->fetchJob($canonicalUrl, $job_id);
        $response = json_decode($job->getContent());
        
        $this->assertEquals('queued', $response->state);
        $this->assertEquals('Single URL', $response->type);
        $this->assertNotNull($response->time_period);
        $this->assertNotNull($response->time_period->start_date_time);
        
        $taskTypeCount = count($response->task_types);
        
        $this->assertEquals(count($expectedTaskUrls), $response->url_count);
        $this->assertEquals($taskTypeCount, $response->task_count);
        $this->assertEquals($taskTypeCount, $response->task_count_by_state->queued);
        
        return;
    }
    
    
    public function testPrepareFullSiteTestWithPublicUserCreatesUrlCountAmmendment() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';        
        $jobCreateResponse = $this->createJob($canonicalUrl);        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertInternalType('integer', $job_id);
        $this->assertGreaterThan(0, $job_id);
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        )));
        
        $this->getJobService()->getEntityRepository()->clear();
        
        $job = $this->getJobService()->getById($job_id);
        $this->assertEquals(1, $job->getAmmendments()->count());
        $this->assertEquals('plan-url-limit-reached:discovered-url-count-11', $job->getAmmendments()->first()->getReason());
    }
    
    
    public function testPrepareFullSiteJobWithNoSitemapSetsJobStateAsFailedNoSitemap() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';        
        $job_id = $this->createJobAndGetId($canonicalUrl);
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        ));
        
        $job = $this->getJobService()->getById($job_id);
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState()->getName(), $job->getState()->getName());
    }
    
    
    public function testPrepareFullSiteJobOwnedByPublicUserWithNoSitemapDoesNotAutostartCrawlJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';        
        $job_id = $this->createJobAndGetId($canonicalUrl);
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        ));
        
        $job = $this->getJobService()->getById($job_id);
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState()->getName(), $job->getState()->getName());
    }    
    
    public function testPrepareFullSiteJobOwnedByNonPublicUserWithNoSitemapDoesAutostartCrawlJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $user = $this->createAndActivateUser('user@example.com', 'password');
        
        $canonicalUrl = 'http://example.com/';        
        $job_id = $this->createJobAndGetId($canonicalUrl, $user->getEmail());
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        ));
        
        $job = $this->getJobService()->getById($job_id);
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState()->getName(), $job->getState()->getName());
        
        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->assertEquals($job_id, $crawlJobContainer->getParentJob()->getId());
        
        $this->assertEquals($this->getJobService()->getQueuedState(), $crawlJobContainer->getCrawlJob()->getState());
    }
    
    
    public function testHandleSitemapContainingHostlessUrls() {
        $canonicalUrl = 'http://example.com/';
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));
        
        $this->assertEquals(0, count($job->getTasks()));
        $this->assertEquals($this->getJobService()->getFailedNoSitemapState(), $job->getState());
    }
    
    public function testHandleSingleIndexLargeSitemap() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $this->getWebSiteService()->getSitemapFinder()->getSitemapRetriever()->setTotalTransferTimeout(0.00001);
        
        $canonicalUrl = 'http://example.com/';        

        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));
        
        $jobControllerResponse = json_decode($this->getJobController('statusAction')->statusAction($job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals(10, $jobControllerResponse->url_count);
    } 
    
    
    public function testHandleLargeCollectionOfSitemaps() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $canonicalUrl = 'http://example.com/';        

        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));
        
        $jobControllerResponse = json_decode($this->getJobController('statusAction')->statusAction($job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals(10, $jobControllerResponse->url_count);
    }   
    
    public function testRejectForUnroutableIp() {
        $canonicalUrl = 'http://192.168.0.173:8020';
        
        $job = $this->getJobService()->create(
                $this->getUserService()->getPublicUser(),
                $this->getWebSiteService()->fetch($canonicalUrl),
                array(),
                array(),
                $this->getJobTypeService()->getByName('Full site'),
                array()
        );    
        
        $this->assertEquals(4, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));
        
        $jobControllerResponse = json_decode($this->getJobController('statusAction')->statusAction($job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals('rejected', $jobControllerResponse->state);
        $this->assertEquals('unroutable', $jobControllerResponse->rejection->reason);
    }     
    
    public function testRejectForUnroutableDomain() {
        $canonicalUrl = 'http://example/';        

        $job = $this->getJobService()->create(
                $this->getUserService()->getPublicUser(),
                $this->getWebSiteService()->fetch($canonicalUrl),
                array(),
                array(),
                $this->getJobTypeService()->getByName('Full site'),
                array()
        );     
        
        $this->assertEquals(4, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));
        
        $jobControllerResponse = json_decode($this->getJobController('statusAction')->statusAction($job->getWebsite(), $job->getId())->getContent());
        
        $this->assertEquals('rejected', $jobControllerResponse->state);
        $this->assertEquals('unroutable', $jobControllerResponse->rejection->reason);
    }
    
    
    public function testMalformedRssUrl() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $canonicalUrl = 'http://beebac.com/';        
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));        
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));  
        
        $this->assertEquals('job-failed-no-sitemap', $job->getState()->getName());     
    }
    
    public function testWithFullSiteTestAndHttpAuthParameters() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $canonicalUrl = 'http://example.com/';        
        
        $httpAuthUsernameKey = 'http-auth-username';
        $httpAuthPasswordKey = 'http-auth-password';
        $httpAuthUsernameValue = 'foo';
        $httpAuthPasswordValue = 'bar';
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null, null, array('html validation'), null, array(
            $httpAuthUsernameKey => $httpAuthUsernameValue,
            $httpAuthPasswordKey => $httpAuthPasswordValue            
        )));       
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        )));  
        
        foreach ($job->getTasks() as $task) {
            $decodedParameters = json_decode($task->getParameters());
            $this->assertTrue(isset($decodedParameters->$httpAuthUsernameKey));
            $this->assertEquals($httpAuthUsernameValue, $decodedParameters->$httpAuthUsernameKey);
            $this->assertTrue(isset($decodedParameters->$httpAuthPasswordKey));
            $this->assertEquals($httpAuthPasswordValue, $decodedParameters->$httpAuthPasswordKey);
        }            
    }
    
    public function testWithSingleUrlTestAndHttpAuthParameters() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $canonicalUrl = 'http://example.com/';        
        
        $httpAuthUsernameKey = 'http-auth-username';
        $httpAuthPasswordKey = 'http-auth-password';
        $httpAuthUsernameValue = 'foo';
        $httpAuthPasswordValue = 'bar';
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null, 'single url', array('html validation'), null, array(
            $httpAuthUsernameKey => $httpAuthUsernameValue,
            $httpAuthPasswordKey => $httpAuthPasswordValue            
        )));

        $decodedParameters = json_decode($job->getTasks()->first()->getParameters());
        $this->assertTrue(isset($decodedParameters->$httpAuthUsernameKey));
        $this->assertEquals($httpAuthUsernameValue, $decodedParameters->$httpAuthUsernameKey);
        $this->assertTrue(isset($decodedParameters->$httpAuthPasswordKey));
        $this->assertEquals($httpAuthPasswordValue, $decodedParameters->$httpAuthPasswordKey);
           
    }
    
    
    public function testWithHttpAuthWithUrlsCollectedViaSitemapViaRobotsTxt() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $canonicalUrl = 'http://example.com/';        
        
        $httpAuthUsernameKey = 'http-auth-username';
        $httpAuthPasswordKey = 'http-auth-password';
        $httpAuthUsernameValue = 'example';
        $httpAuthPasswordValue = 'password';        
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null, 'full site', null, null, array(
            $httpAuthUsernameKey => $httpAuthUsernameValue,
            $httpAuthPasswordKey => $httpAuthPasswordValue            
        )));
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        ))); 
        
        $this->assertTrue(count($job->getTasks()) > 0);
        
        $this->assertEquals($httpAuthUsernameValue, $this->getJobPreparationService()->getWebsiteService()->getSitemapFinder()->getBaseRequest()->getUsername());
        $this->assertEquals($httpAuthPasswordValue, $this->getJobPreparationService()->getWebsiteService()->getSitemapFinder()->getBaseRequest()->getPassword());
    }
    
    
    public function testWithHttpAuthWithUrlsCollectedSitemapViaGuessingPath() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $canonicalUrl = 'http://example.com/';        
        
        $httpAuthUsernameKey = 'http-auth-username';
        $httpAuthPasswordKey = 'http-auth-password';
        $httpAuthUsernameValue = 'example';
        $httpAuthPasswordValue = 'password';        
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null, 'full site', null, null, array(
            $httpAuthUsernameKey => $httpAuthUsernameValue,
            $httpAuthPasswordKey => $httpAuthPasswordValue            
        )));
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        ))); 
        
        $this->assertTrue(count($job->getTasks()) > 0);
        
        $this->assertEquals($httpAuthUsernameValue, $this->getJobPreparationService()->getWebsiteService()->getSitemapFinder()->getBaseRequest()->getUsername());
        $this->assertEquals($httpAuthPasswordValue, $this->getJobPreparationService()->getWebsiteService()->getSitemapFinder()->getBaseRequest()->getPassword());        
    }    
    
    public function testWithHttpAuthWithUrlsCollectedViaRssFeed() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        $canonicalUrl = 'http://example.com/';        
        
        $httpAuthUsernameKey = 'http-auth-username';
        $httpAuthPasswordKey = 'http-auth-password';
        $httpAuthUsernameValue = 'example';
        $httpAuthPasswordValue = 'password';        
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, null, 'full site', null, null, array(
            $httpAuthUsernameKey => $httpAuthUsernameValue,
            $httpAuthPasswordKey => $httpAuthPasswordValue            
        )));
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        ))); 
        
        $this->assertTrue(count($job->getTasks()) > 0);        
              
        $this->assertEquals($httpAuthUsernameValue, $this->getJobPreparationService()->getWebsiteService()->getWebsiteRssFeedFinder($job->getWebsite(), $job->getParameters())->getBaseRequest()->getUsername());
        $this->assertEquals($httpAuthPasswordValue, $this->getJobPreparationService()->getWebsiteService()->getWebsiteRssFeedFinder($job->getWebsite(), $job->getParameters())->getBaseRequest()->getPassword());
                
    } 
    
    
    public function testCrawlJobTakesParametersOfParentJob() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $user = $this->createAndActivateUser('user@example.com', 'password');
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, $user->getEmail(), 'full site', array('HTML validation'), null, array(
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'
        )));
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        ));
        
        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->assertEquals($crawlJobContainer->getParentJob()->getParameters(), $crawlJobContainer->getCrawlJob()->getParameters());
    } 
    
    
    public function testCrawlJobTaskTakesHttpAuthParameters() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $user = $this->createAndActivateUser('user@example.com', 'password');
        
        $httpAuthUsernameKey = 'http-auth-username';
        $httpAuthPasswordKey = 'http-auth-password';
        $httpAuthUsername = 'example';
        $httpAuthPassword = 'password';
        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl, $user->getEmail(), 'full site', array('HTML validation'), null, array(
            $httpAuthUsernameKey => $httpAuthUsername,
            $httpAuthPasswordKey=> $httpAuthPassword
        )));
        
        $this->runConsole('simplytestable:job:prepare', array(
            $job->getId() =>  true
        ));
        
        $this->assertTrue($this->getCrawlJobContainerService()->hasForJob($job));
        $crawlJob = $this->getCrawlJobContainerService()->getForJob($job)->getCrawlJob();
        
        $taskParameters = json_decode($crawlJob->getTasks()->first()->getParameters());
        
        $this->assertTrue(isset($taskParameters->$httpAuthUsernameKey));
        $this->assertTrue(isset($taskParameters->$httpAuthPasswordKey));
        $this->assertEquals($httpAuthUsername, $taskParameters->$httpAuthUsernameKey);
        $this->assertEquals($httpAuthPassword, $taskParameters->$httpAuthPasswordKey);
    }     

}
