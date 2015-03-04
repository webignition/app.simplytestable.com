<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\InvalidLabel\Team;

class LeaderTest extends TeamTest {

    protected function getCurrentUser() {
        return $this->leader;
    }
}