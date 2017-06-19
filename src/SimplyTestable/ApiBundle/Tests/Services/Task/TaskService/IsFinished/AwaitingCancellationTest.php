<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class AwaitingCancellationTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return false;
    }

}
