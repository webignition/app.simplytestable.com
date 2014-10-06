<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\HttpAuth;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class CrawlJobTest extends BaseSimplyTestableTestCase {    

    const HTTP_AUTH_USERNAME_KEY = 'http-auth-username';
    const HTTP_AUTH_PASSWORD_KEY = 'http-auth-password';
    const HTTP_AUTH_USERNAME = 'foo';
    const HTTP_AUTH_PASSWORD = 'bar';  
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    private $job;
    
    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->job = $this->getJobService()->getById($this->createAndResolveJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail(), 'full site', array('html validation'), null, array(
            self::HTTP_AUTH_USERNAME_KEY => self::HTTP_AUTH_USERNAME,
            self::HTTP_AUTH_PASSWORD_KEY => self::HTTP_AUTH_PASSWORD
        )));         
        
        $this->queuePrepareHttpFixturesForCrawlJob($this->job->getWebsite()->getCanonicalUrl());
        
        $this->getJobPreparationService()->prepare($this->job);
    }
    
    
    public function testCrawlJobTaskTakesHttpAuthParameters() {   
        $crawlJob = $this->getCrawlJobContainerService()->getForJob($this->job)->getCrawlJob();
        
        $taskParameters = json_decode($crawlJob->getTasks()->first()->getParameters());
        
        $this->assertTrue(isset($taskParameters->{self::HTTP_AUTH_USERNAME_KEY}));
        $this->assertTrue(isset($taskParameters->{self::HTTP_AUTH_PASSWORD_KEY}));
        $this->assertEquals(self::HTTP_AUTH_USERNAME, $taskParameters->{self::HTTP_AUTH_USERNAME_KEY});
        $this->assertEquals(self::HTTP_AUTH_PASSWORD, $taskParameters->{self::HTTP_AUTH_PASSWORD_KEY});
    }      

}
