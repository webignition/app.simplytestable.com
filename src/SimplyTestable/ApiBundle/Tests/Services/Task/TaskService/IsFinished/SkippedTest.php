<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class SkippedTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return true;
    }

}
