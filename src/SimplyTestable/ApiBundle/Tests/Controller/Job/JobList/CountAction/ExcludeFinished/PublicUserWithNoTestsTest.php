<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction\ExcludeFinished;

class PublicUserWithNoTestsTest extends ExcludeFinishedTest {

    protected function getExpectedCountValue() {
        return 0;
    }

    protected function getCanonicalUrls() {
        return array();
    }
}

