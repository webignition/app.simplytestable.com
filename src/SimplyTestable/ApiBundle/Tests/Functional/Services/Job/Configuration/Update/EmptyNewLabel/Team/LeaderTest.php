<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\EmptyNewLabel\Team;

class LeaderTest extends TeamTest {

    protected function getCurrentUser() {
        return $this->leader;
    }
}