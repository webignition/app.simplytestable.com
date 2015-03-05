<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Delete\Success\Team;

class LeaderTest extends TeamTest {

    protected function getCurrentUser() {
        return $this->leader;
    }
}