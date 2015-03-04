<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\HasExisting\Team;

class Member1Test extends TeamTest {

    protected function getCurrentUser() {
        return $this->member1;
    }
}