<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\FilterByUrl;

class ExactMatchTest extends FilterByUrlTest {    
   
    protected function getExpectedWebsitesList() {
        return array('http://example.com/foo');
    }

    protected function getFilter() {
        return 'http://example.com/foo';
    }
}


