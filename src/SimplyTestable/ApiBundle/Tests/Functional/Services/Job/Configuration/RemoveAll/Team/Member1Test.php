<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\RemoveAll\Team;

class Member1Test extends TeamTest {

    protected function getCurrentUser() {
        return $this->member1;
    }
}