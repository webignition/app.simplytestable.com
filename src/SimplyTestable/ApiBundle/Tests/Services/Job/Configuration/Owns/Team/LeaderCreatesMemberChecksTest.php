<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Owns\Team;

class LeaderCreatesMemberChecksTest extends TeamTest {


    protected function getJobConfigurationUser()
    {
        return $this->leader;
    }

    protected function getServiceUser()
    {
        return $this->member;
    }
}
