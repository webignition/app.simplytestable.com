<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class CancelledTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}