<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\FilterByUrl;

use SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\AbstractListTest;

abstract class FilterByUrlTest extends AbstractListTest {       
    
    const JOB_LIMIT = 10;
    
    private $canonicalUrls = array(
        'http://example.com/',
        'http://example.com/foo',
        'https://example.com/',
        'https://example.com/foo',
        'http://foo.example.com/',
        'https://foo.example.com/'
    );
    
    
    private $list;
    
    
    public function setUp() {
        parent::setUp();
        
        foreach ($this->canonicalUrls as $canonicalUrl) {                        
            $this->createJobAndGetId($canonicalUrl, null, 'single url');
        }        
        
        $this->list = json_decode($this->getJobListController('listAction', array(), array(
            'url-filter' => $this->getFilter()
        ))->listAction(self::JOB_LIMIT)->getContent());         
    }
    
    
    abstract protected function getExpectedListLength();
    abstract protected function getFilter();
    
    
    public function testListLength() {        
        $this->assertEquals($this->getExpectedListLength(), count($this->list->jobs));         
    }
    
    
    public function testListJobsMatchFilter() {        
        foreach ($this->list->jobs as $job) {
            $this->assertWebsiteMatchesFilter($job->website);
        }       
    }
    
    
    private function assertWebsiteMatchesFilter($website) {
        $regexp = preg_quote($this->getFilter());
        $regexp = str_replace('/', '\/', $regexp);
        $regexp = str_replace('\*', '.?', $regexp);
        
        $this->assertRegExp('/' . $regexp . '/', $website, 'Website "' . $website . '" does not match filter "' . $this->getFilter() . '"');
    }    
    
    
   
    
}


