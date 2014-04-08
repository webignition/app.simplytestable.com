<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeFinished\PublicUserWithLimitAndOnlyNewTests;

class OneTest extends PublicUserWithLimitAndOnlyNewTestsTest {

    protected function getExpectedListLength() {
        return 1;
    }
}


