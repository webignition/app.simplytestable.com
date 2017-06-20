<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task\TaskService\IsFinished;

class AwaitingCancellationTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return false;
    }

}
