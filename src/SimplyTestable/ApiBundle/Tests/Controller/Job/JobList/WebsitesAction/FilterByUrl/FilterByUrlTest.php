<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\FilterByUrl;

use SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\ContentTest;

abstract class FilterByUrlTest extends ContentTest {       
    
    private $canonicalUrls = array(
        'http://example.com/',
        'http://example.com/foo',
        'https://example.com/',
        'https://example.com/foo',
        'http://foo.example.com/',
        'https://foo.example.com/'        
    );
    
    protected function getQueryParameters() {
        return array(
            'url-filter' => $this->getFilter()
        );
    }   
    
    protected function getCanonicalUrls() {
        return $this->canonicalUrls;
    }
    
}


