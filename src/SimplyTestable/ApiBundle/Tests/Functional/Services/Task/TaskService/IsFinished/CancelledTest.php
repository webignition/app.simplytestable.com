<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task\TaskService\IsFinished;

class CancelledTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return true;
    }

}
