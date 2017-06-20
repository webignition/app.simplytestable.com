<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task\TaskService\IsFinished;

class SkippedTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return true;
    }

}
