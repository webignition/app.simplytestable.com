<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class QueuedTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
