<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\FilterByUrl;

class IgnorePathTest extends FilterByUrlTest {

    protected function getExpectedListLength() {
        return 2;
    }

    protected function getFilter() {
        return 'http://example.com/*';
    }

    protected function getExpectedJobListUrls() {
        return array(
            'http://example.com/foo',
            'http://example.com/',
        );
    }

}


