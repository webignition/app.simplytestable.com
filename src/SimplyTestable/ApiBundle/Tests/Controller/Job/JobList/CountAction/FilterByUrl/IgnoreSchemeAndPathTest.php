<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction\FilterByUrl;

class IgnoreSchemeAndPathTest extends FilterByUrlTest {
   
    protected function getExpectedCountValue() {
        return 4;
    }

    protected function getFilter() {
        return '*://example.com/*';
    }    

}


