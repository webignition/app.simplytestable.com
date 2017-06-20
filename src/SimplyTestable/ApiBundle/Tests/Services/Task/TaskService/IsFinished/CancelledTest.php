<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class CancelledTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return true;
    }

}
