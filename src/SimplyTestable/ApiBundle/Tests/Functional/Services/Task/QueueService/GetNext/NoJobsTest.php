<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task\QueueService\GetNext;

use SimplyTestable\ApiBundle\Tests\Functional\Services\Task\QueueService\ServiceTest;

class NoJobsTest extends ServiceTest {

    public function testGetsEmptySet() {
        $this->assertEquals([], $this->getService()->getNext());
    }

}
