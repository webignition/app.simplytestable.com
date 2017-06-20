<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task\TaskService\IsFinished;

class InProgressTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return false;
    }

}
