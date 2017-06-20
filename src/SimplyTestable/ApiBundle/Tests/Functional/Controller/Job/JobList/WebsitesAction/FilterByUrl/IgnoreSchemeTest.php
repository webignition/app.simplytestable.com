<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\WebsitesAction\FilterByUrl;

class IgnoreSchemeTest extends FilterByUrlTest {

    protected function getExpectedWebsitesList() {
        return array(
            'http://example.com/',
            'https://example.com/'
        );
    }

    protected function getFilter() {
        return '*://example.com/';
    }

}


