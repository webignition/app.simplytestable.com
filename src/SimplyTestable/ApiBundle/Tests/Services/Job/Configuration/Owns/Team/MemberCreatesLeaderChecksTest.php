<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Job\Configuration\Owns\Team;

class MemberCreatesLeaderChecksTest extends TeamTest {


    protected function getJobConfigurationUser()
    {
        return $this->member;
    }

    protected function getServiceUser()
    {
        return $this->leader;
    }
}
