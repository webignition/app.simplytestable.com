<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class QueuedForAssignmentTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return false;
    }

}
