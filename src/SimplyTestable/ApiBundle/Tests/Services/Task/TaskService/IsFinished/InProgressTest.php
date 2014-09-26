<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class InProgressTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
