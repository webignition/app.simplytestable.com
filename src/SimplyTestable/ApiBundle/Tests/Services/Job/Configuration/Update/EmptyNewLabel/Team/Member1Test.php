<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Update\EmptyNewLabel\Team;

class Member1Test extends TeamTest {

    protected function getCurrentUser() {
        return $this->member1;
    }
}