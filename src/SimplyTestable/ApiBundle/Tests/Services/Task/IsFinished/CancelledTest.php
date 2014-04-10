<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\IsFinished;

class CancelledTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}
