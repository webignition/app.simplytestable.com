<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\FilterByUrl;

class IgnoreSchemeAndPathTest extends FilterByUrlTest {
   
    protected function getExpectedWebsitesList() {
        return array(
            'http://example.com/',
            'http://example.com/foo',
            'https://example.com/',
            'https://example.com/foo'            
        );
    }

    protected function getFilter() {
        return '*://example.com/*';
    }    

}


