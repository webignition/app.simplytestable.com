<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\RemoveAll\Team;

class LeaderTest extends TeamTest {

    protected function getCurrentUser() {
        return $this->leader;
    }
}