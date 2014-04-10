<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\IsFinished;

class QueuedTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
