<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\IsFinished;

class SkippedTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}
