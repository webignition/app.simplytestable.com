<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\FilterByUrl;

class ExactMatchTest extends FilterByUrlTest {

    protected function getExpectedListLength() {
        return 1;
    }

    protected function getFilter() {
        return 'http://example.com/foo';
    }

    protected function getExpectedJobListUrls() {
        return array(
            'http://example.com/foo'
        );
    }

}


