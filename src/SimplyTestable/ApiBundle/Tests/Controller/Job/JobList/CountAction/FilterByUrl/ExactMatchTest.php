<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction\FilterByUrl;

class ExactMatchTest extends FilterByUrlTest {    
   
    protected function getExpectedCountValue() {
        return 1;
    }

    protected function getFilter() {
        return 'http://example.com/foo';
    }
}


