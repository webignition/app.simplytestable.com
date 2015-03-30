<?php

namespace SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\ExecuteCommand\Success;

use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use SimplyTestable\ApiBundle\Tests\Command\ScheduledJob\ExecuteCommand\WithScheduledJobTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

abstract class SuccessTest extends WithScheduledJobTest {

    /**
     * @var Job
     */
    protected  $latestJob;


    protected function getExpectedReturnCode() {
        return ExecuteCommand::RETURN_CODE_OK;
    }


    public function setUp() {
        parent::setUp();
        $this->latestJob = $this->getJobService()->getEntityRepository()->findAll()[0];
    }
}
