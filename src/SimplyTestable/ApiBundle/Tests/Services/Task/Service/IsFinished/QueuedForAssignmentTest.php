<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\Service\IsFinished;

class QueuedForAssignmentTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
