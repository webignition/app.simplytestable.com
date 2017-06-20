<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\WebsitesAction\ExcludeFinished;

class PublicUserWithNoTestsTest extends ExcludeFinishedTest {

    protected function getExpectedWebsitesList() {
        return array();
    }

    protected function getCanonicalUrls() {
        return array();
    }
}


