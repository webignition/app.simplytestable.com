<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task\TaskService\IsFinished;

class QueuedForAssignmentTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return false;
    }

}
