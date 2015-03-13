<?php

namespace SimplyTestable\ApiBundle\Tests\Services\ScheduledJob\GetList\Team;

class LeaderTest extends TeamTest {

    protected function getServiceRequestUser()
    {
        return $this->leader;
    }
}