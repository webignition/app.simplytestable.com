<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\Cookies\RegularJob;

use SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\Cookies\ServiceTest;

abstract class RegularJobTest extends ServiceTest {    
    
    public function setUp() {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getHttpFixtureMessagesFromPath($this->getFixturesDataPath(). '/HttpResponses')));              
        $this->getJobPreparationService()->prepare($this->job);
    }
    
    public function testCookieParametersAreSetOnTasks() {        
        $this->assertGreaterThan(0, $this->job->getTasks()->count());
        
        foreach ($this->job->getTasks() as $task) {            
            $decodedParameters = $task->getParametersArray();            
            $this->assertTrue(isset($decodedParameters['cookies']));
            $this->assertEquals($this->cookies, $decodedParameters['cookies']);         
        }            
    }
    
    
    public function testCookiesAreSetOnRequests() {
        foreach ($this->getHttpClientService()->getHistoryPlugin()->getAll() as $httpTransaction) {
            $this->assertEquals($this->getExpectedCookieValues(), $httpTransaction['request']->getCookies());
        }
    }   
    
    /**
     * 
     * @return array
     */
    private function getExpectedCookieValues() {
        $nameValueArray = array();
        
        foreach ($this->cookies as $cookie) {
            $nameValueArray[$cookie['name']] = $cookie['value'];
        }
        
        return $nameValueArray;
    }      

}
