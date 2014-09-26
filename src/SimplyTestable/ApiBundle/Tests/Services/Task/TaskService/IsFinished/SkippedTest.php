<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class SkippedTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}
