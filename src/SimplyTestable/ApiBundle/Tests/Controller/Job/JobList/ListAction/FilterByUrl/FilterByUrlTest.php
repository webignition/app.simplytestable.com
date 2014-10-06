<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\FilterByUrl;

use SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ListContentTest;

abstract class FilterByUrlTest extends ListContentTest {       
    
    private $canonicalUrls = array(
        'http://example.com/',
        'http://example.com/foo',
        'https://example.com/',
        'https://example.com/foo',
        'http://foo.example.com/',
        'https://foo.example.com/'        
    );

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }
    
    protected function getQueryParameters() {
        return array(
            'url-filter' => $this->getFilter()
        );
    }   
    
    protected function getCanonicalUrls() {
        return $this->canonicalUrls;
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


