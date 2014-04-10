<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\IsFinished;

class InProgressTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
