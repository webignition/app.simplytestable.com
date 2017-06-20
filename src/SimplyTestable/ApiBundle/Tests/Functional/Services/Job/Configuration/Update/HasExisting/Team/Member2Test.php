<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Update\HasExisting\Team;

class Member2Test extends TeamTest {

    protected function getCurrentUser() {
        return $this->member2;
    }
}