<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class QueuedForAssignmentTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
