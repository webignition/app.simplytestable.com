<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\Service\IsFinished;

class InProgressTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
