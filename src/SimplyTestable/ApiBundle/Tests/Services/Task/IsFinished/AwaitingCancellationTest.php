<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\IsFinished;

class AwaitingCancellationTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
