<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\GetList\Team;

class MemberTest extends TeamTest {

    protected function getServiceRequestUser()
    {
        return $this->member;
    }
}