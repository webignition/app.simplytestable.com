<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\FilterByUrl;

class IgnoreSchemeAndPathTest extends FilterByUrlTest {
   
    protected function getExpectedListLength() {
        return 4;
    }

    protected function getFilter() {
        return '*://example.com/*';
    }

}


