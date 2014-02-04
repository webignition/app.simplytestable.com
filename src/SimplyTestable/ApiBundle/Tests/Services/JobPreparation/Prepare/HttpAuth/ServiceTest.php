<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\HttpAuth;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class ServiceTest extends BaseSimplyTestableTestCase {    

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
        
        $this->queueResolveHttpFixture();
        
        $this->job = $this->getJobService()->getById($this->createAndResolveJob(self::DEFAULT_CANONICAL_URL, null, null, array('html validation'), null, array(
            self::HTTP_AUTH_USERNAME_KEY => self::HTTP_AUTH_USERNAME,
            self::HTTP_AUTH_PASSWORD_KEY => self::HTTP_AUTH_PASSWORD
        ))); 
        
        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath($this->getName()). '/HttpResponses')));      
        
        $this->getJobPreparationService()->prepare($this->job);
    }

    
    public function testParametersAreSetOnTasks() {        
        foreach ($this->job->getTasks() as $task) {
            $decodedParameters = json_decode($task->getParameters());
            $this->assertTrue(isset($decodedParameters->{self::HTTP_AUTH_USERNAME_KEY}));
            $this->assertEquals(self::HTTP_AUTH_USERNAME, $decodedParameters->{self::HTTP_AUTH_USERNAME_KEY});
            $this->assertTrue(isset($decodedParameters->{self::HTTP_AUTH_PASSWORD_KEY}));
            $this->assertEquals(self::HTTP_AUTH_PASSWORD, $decodedParameters->{self::HTTP_AUTH_PASSWORD_KEY});            
        }            
    }
    
    
    public function testWithUrlsCollectedViaSitemapViaRobotsTxt() {
        $this->assertGreaterThan(0, $this->job->getTasks()->count());
        
        $this->assertEquals(self::HTTP_AUTH_USERNAME, $this->getJobPreparationService()->getWebsiteService()->getSitemapFinder()->getBaseRequest()->getUsername());
        $this->assertEquals(self::HTTP_AUTH_PASSWORD, $this->getJobPreparationService()->getWebsiteService()->getSitemapFinder()->getBaseRequest()->getPassword());        
    }
    
    
    public function testWithUrlsCollectedSitemapViaGuessingPath() {
        $this->assertGreaterThan(0, $this->job->getTasks()->count());
        
        $this->assertEquals(self::HTTP_AUTH_USERNAME, $this->getJobPreparationService()->getWebsiteService()->getSitemapFinder()->getBaseRequest()->getUsername());
        $this->assertEquals(self::HTTP_AUTH_PASSWORD, $this->getJobPreparationService()->getWebsiteService()->getSitemapFinder()->getBaseRequest()->getPassword());        
    } 
    
    
    public function testWithUrlsCollectedViaRssFeed() {
        $this->assertGreaterThan(0, $this->job->getTasks()->count());
        
        $this->assertEquals(self::HTTP_AUTH_USERNAME, $this->getJobPreparationService()->getWebsiteService()->getSitemapFinder()->getBaseRequest()->getUsername());
        $this->assertEquals(self::HTTP_AUTH_PASSWORD, $this->getJobPreparationService()->getWebsiteService()->getSitemapFinder()->getBaseRequest()->getPassword());        
    }      

}
