<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ExcludeFinished\PublicUserWithLimitAndOnlyNewTests;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ExcludeFinished\ExcludeFinishedTest;

abstract class PublicUserWithLimitAndOnlyNewTestsTest extends ExcludeFinishedTest {

    private $canonicalUrls = array(
        'http://one.example.com/',
        'http://two.example.com/',
        'http://three.example.com/'
    );

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }

    protected function getCanonicalUrls() {
        return $this->canonicalUrls;
    }

    protected function getExpectedJobListUrls() {
        return array_slice(array_reverse($this->canonicalUrls), 0, $this->getExpectedListLength());
    }

    protected function getLimit() {
        return $this->getExpectedListLength();
    }
}


