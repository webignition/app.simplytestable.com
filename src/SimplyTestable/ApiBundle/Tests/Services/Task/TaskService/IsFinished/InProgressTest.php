<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class InProgressTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return false;
    }

}
