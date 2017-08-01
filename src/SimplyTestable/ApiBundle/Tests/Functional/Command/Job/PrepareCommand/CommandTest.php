<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job\PrepareCommand;

use SimplyTestable\ApiBundle\Tests\Functional\Command\CommandTest as BaseCommandTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;

abstract class CommandTest extends BaseCommandTest {

    /**
     * @var int
     */
    protected $returnCode;


    /**
     * @var Job
     */
    protected $job;

    protected function setUp() {
        parent::setUp();

        $this->job = $this->getJob();

        $this->clearRedis();
        $this->preCall();

        $this->returnCode = $this->executeCommand($this->getCommandName(), [
            'id' => $this->job->getId()
        ]);
    }

    abstract protected function getJob();
    abstract protected function getExpectedReturnCode();

    protected function preCall() {}

    public function testReturnCode() {
        $this->assertEquals($this->getExpectedReturnCode(), $this->returnCode);
    }

}
