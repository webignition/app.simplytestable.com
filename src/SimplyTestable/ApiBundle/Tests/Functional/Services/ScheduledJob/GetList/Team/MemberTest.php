<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\ScheduledJob\GetList\Team;

class MemberTest extends TeamTest {

    protected function getServiceRequestUser()
    {
        return $this->member;
    }
}