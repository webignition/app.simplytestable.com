<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Delete\Success\Team;

class Member1Test extends TeamTest {

    protected function getCurrentUser() {
        return $this->member1;
    }
}