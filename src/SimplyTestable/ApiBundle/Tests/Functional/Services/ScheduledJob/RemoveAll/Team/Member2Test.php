<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\RemoveAll\Team;

class Member2Test extends TeamTest {

    protected function getCurrentUser() {
        return $this->member2;
    }
}