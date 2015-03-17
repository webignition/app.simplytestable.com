<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\RemoveAll\Team;

class Member1Test extends TeamTest {

    protected function getCurrentUser() {
        return $this->member1;
    }
}