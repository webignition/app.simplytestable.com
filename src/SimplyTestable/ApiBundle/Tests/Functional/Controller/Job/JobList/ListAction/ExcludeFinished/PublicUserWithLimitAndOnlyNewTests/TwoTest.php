<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ExcludeFinished\PublicUserWithLimitAndOnlyNewTests;

class TwoTest extends PublicUserWithLimitAndOnlyNewTestsTest {

    protected function getExpectedListLength() {
        return 2;
    }
}


