<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\FilterByUrl;

class IgnorePathTest extends FilterByUrlTest {

    protected function getExpectedCountValue() {
        return 2;
    }

    protected function getFilter() {
        return 'http://example.com/*';
    }

}


