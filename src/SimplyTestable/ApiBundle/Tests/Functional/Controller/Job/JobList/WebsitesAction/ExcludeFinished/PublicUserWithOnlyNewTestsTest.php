<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\WebsitesAction\ExcludeFinished;

class PublicUserWithOnlyNewTestsTest extends ExcludeFinishedTest {

    private $canonicalUrls = array(
        'http://one.example.com/',
        'http://two.example.com/',
        'http://three.example.com/'
    );

    protected function getExpectedWebsitesList() {
        $expectedWebsitesList = $this->getCanonicalUrls();
        sort($expectedWebsitesList);
        return $expectedWebsitesList;
    }

    protected function getCanonicalUrls() {
        return $this->canonicalUrls;
    }
}


