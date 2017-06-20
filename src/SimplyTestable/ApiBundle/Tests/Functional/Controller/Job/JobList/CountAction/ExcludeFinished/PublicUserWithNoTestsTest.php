<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction\ExcludeFinished;

class PublicUserWithNoTestsTest extends ExcludeFinishedTest {

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }

    protected function getExpectedCountValue() {
        return 0;
    }

    protected function getCanonicalUrls() {
        return array();
    }
}


