<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class CompletedTest extends isFinishedTest {
    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}