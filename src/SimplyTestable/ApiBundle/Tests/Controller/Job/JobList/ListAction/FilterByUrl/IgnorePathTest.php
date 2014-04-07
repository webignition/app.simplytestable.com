<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\FilterByUrl;

class IgnorePathTest extends FilterByUrlTest {
   
    protected function getExpectedListLength() {
        return 2;
    }

    protected function getFilter() {
        return 'http://example.com/*';
    }

}


