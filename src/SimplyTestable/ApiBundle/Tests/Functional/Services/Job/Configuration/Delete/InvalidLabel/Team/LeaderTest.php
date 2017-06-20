<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\InvalidLabel\Team;

class LeaderTest extends TeamTest {

    protected function getCurrentUser() {
        return $this->leader;
    }
}