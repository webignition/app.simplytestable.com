<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\WebsitesAction\FilterByUrl;

class IgnorePathTest extends FilterByUrlTest {

    protected function getExpectedWebsitesList() {
        return array(
            'http://example.com/',
            'http://example.com/foo'

        );
    }

    protected function getFilter() {
        return 'http://example.com/*';
    }

}


