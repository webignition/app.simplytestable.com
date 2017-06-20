<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Delete\Success\Team;

class Member1Test extends TeamTest {

    protected function getCurrentUser() {
        return $this->member1;
    }
}