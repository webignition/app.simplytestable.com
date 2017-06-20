<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction\ExcludeFinished\PublicUserWithLimitAndOnlyNewTests;

class OneTest extends PublicUserWithLimitAndOnlyNewTestsTest {

    protected function getExpectedListLength() {
        return 1;
    }
}


