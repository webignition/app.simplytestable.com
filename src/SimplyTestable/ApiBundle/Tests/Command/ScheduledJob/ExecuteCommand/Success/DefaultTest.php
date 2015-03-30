<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\ExecuteCommand\Success;

class DefaultTest extends SuccessTest {

    public function testResultantJobTaskTypeCollectionCount() {
        $this->assertEquals(2, $this->latestJob->getTaskTypeCollection()->count());
    }

}
