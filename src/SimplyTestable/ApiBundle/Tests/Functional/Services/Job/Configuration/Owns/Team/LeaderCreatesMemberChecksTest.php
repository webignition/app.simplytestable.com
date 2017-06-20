<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Job\Configuration\Owns\Team;

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
