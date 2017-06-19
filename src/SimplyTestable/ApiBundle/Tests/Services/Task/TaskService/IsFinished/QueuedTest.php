<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class QueuedTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return false;
    }

}
