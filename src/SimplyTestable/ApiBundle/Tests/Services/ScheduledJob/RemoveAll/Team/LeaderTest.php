<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\RemoveAll\Team;

class LeaderTest extends TeamTest {

    protected function getCurrentUser() {
        return $this->leader;
    }
}