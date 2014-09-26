<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class AwaitingCancellationTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
