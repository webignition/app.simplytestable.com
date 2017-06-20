<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task\TaskService\IsFinished;

class CompletedTest extends IsFinishedTest {


    protected function getExpectedIsFinished() {
        return true;
    }

}
