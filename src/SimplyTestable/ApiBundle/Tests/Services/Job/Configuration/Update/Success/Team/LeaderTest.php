<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\Success\Team;

class LeaderTest extends TeamTest {

    protected function getCurrentUser() {
        return $this->leader;
    }
}