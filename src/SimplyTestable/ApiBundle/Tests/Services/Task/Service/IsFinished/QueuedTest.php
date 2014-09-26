<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\Service\IsFinished;

class QueuedTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
