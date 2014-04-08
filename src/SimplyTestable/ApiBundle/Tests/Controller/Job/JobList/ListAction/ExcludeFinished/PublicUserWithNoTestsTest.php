<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeFinished;

class PublicUserWithNoTestsTest extends ExcludeFinishedTest {

    protected function getExpectedListLength() {
        return 0;
    }

    protected function getCanonicalUrls() {
        return array();
    }

    protected function getExpectedJobListUrls() {
        return array();
    }

}


