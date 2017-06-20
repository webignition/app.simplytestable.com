<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\FilterByUrl;

class IgnoreSchemeAndPathTest extends FilterByUrlTest {

    protected function getExpectedListLength() {
        return 4;
    }

    protected function getFilter() {
        return '*://example.com/*';
    }

    protected function getExpectedJobListUrls() {
        return array(
            'https://example.com/foo',
            'https://example.com/',
            'http://example.com/foo',
            'http://example.com/',
        );
    }

}


