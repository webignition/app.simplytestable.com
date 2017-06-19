<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class CompletedTest extends IsFinishedTest {


    protected function getExpectedIsFinished() {
        return true;
    }

}
