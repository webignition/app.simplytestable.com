<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\IsFinished;

class QueuedForAssignmentTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
