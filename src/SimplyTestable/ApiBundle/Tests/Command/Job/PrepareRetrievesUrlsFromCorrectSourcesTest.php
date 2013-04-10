<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

class PrepareRetrievesUrlsFromCorrectSourcesTest extends BaseSimplyTestableTestCase {    
    
    const EXPECTED_TASK_TYPE_COUNT = 3;
    
    public function setUp() {
        parent::setUp();
        self::setupDatabase();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName()). '/HttpResponses'));
    }
    
    public function testNoRobotsTxtNoSitemapXmlNoRssNoAtomGetsNoUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            0,
            'no-sitemap',
            array()
        );        
    }    
    
    
    public function testNoRobotsTxtNoSitemapXmlNoRssHasAtomGetsAtomUrls() {        
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            1,
            'queued',
            array(
                'http://example.com/2003/12/13/atom03'
            )
        );
    } 
    
    public function testNoRobotsTxtNoSitemapXmlHasRssNoAtomGetsRssUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            1,
            'queued',
            array(
                'http://example.com/url/'
            )
        );      
    } 
    
    
    public function testNoRobotsTxtHasSitemapXmlGetsSitemapXmlUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            3,
            'queued',
            array(
                'http://www.example.com/?id=who',
                'http://www.example.com/?id=what',
                'http://www.example.com/?id=how'
            )
        );
    }
    
    
    public function testNoRobotsTxtHasSitemapTxtGetsSitemapTxtUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            3,
            'queued',
            array(
                'http://www.example.com/text/one/',
                'http://www.example.com/text/two/',
                'http://www.example.com/text/three/'
            )
        );
    }    
    

    public function testHasRobotsTxtNoSitemapGetsNoRssNoAtomGetsNoUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            0,
            'no-sitemap',
            array()
        );
    } 
    
    public function testHasRobotsTxtNoSitemapGetsNoRssHasAtomGetsAtomUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            1,
            'queued',
            array(
                'http://example.com/2003/12/13/atom03'
            )
        );
    }    
    
    public function testHasRobotsTxtNoSitemapGetsHasRssNoAtomGetsRssUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            1,
            'queued',
            array(
                'http://example.com/url/'
            )
        ); 
    }    
    
    public function testHasRobotsTxtHasSitemapXmlGetsSitemapXmlUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            3,
            'queued',
            array(
                'http://www.example.com/?id=who',
                'http://www.example.com/?id=what',
                'http://www.example.com/?id=how'
            )
        );
    }  
    
    
    public function testHasRobotsTxtHasSitemapTxtGetsSitemapTxtUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            3,
            'queued',
            array(
                'http://www.example.com/text/one/',
                'http://www.example.com/text/two/',
                'http://www.example.com/text/three/'
            )
        );
    }    
    
    public function testHasRobotsTxtHasSitemapXmlHasSitemapTxtGetsSitemapXmlAndSitemapTxtUrls() {
        $this->prepareJobAndPostAssertions(
            'http://example.com/',
            6,
            'queued',
            array(
                'http://www.example.com/?id=who',
                'http://www.example.com/?id=what',
                'http://www.example.com/?id=how',             
                'http://www.example.com/text/one/',
                'http://www.example.com/text/two/',
                'http://www.example.com/text/three/'
            )
        );
    }      
    
    private function prepareJobAndPostAssertions($canonicalUrl, $expectedUrlCount, $expectedJobEndState, $expectedTaskSetUrls) {
        $expectedTaskCount = self::EXPECTED_TASK_TYPE_COUNT * $expectedUrlCount;        
        
        $jobCreateResponse = $this->createJob($canonicalUrl);        
        $job_id = $this->getJobIdFromUrl($jobCreateResponse->getTargetUrl());
        
        $this->assertInternalType('integer', $job_id);
        $this->assertGreaterThan(0, $job_id);
        
        $this->assertEquals(0, $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true
        )));
        
        $this->getJobService()->getEntityRepository()->clear();
        
        $jobResponse = json_decode($this->fetchJob($canonicalUrl, $job_id)->getContent());
        
        $this->assertEquals($expectedJobEndState, $jobResponse->state);
        $this->assertEquals(self::EXPECTED_TASK_TYPE_COUNT, count($jobResponse->task_types));
        $this->assertEquals($expectedUrlCount, $jobResponse->url_count);
        $this->assertEquals($expectedTaskCount, $jobResponse->task_count);        
        
        $job = $this->getJobService()->getById($job_id);
        $tasks = $job->getTasks();
        
        $this->assertEquals($expectedTaskCount, $tasks->count());

        for ($subsetOffset = 0; $subsetOffset < $expectedTaskCount; $subsetOffset += 3) {
            $taskSubset = $tasks->slice($subsetOffset, self::EXPECTED_TASK_TYPE_COUNT);
            
            foreach ($taskSubset as $task) {
                $this->assertEquals($expectedTaskSetUrls[$subsetOffset / self::EXPECTED_TASK_TYPE_COUNT], $task->getUrl());
            }
        }        
    }

}
