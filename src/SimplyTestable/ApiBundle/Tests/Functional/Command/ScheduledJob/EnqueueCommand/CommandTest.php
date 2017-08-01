<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\ScheduledJob\EnqueueCommand;

use SimplyTestable\ApiBundle\Tests\Functional\Command\CommandTest as BaseCommandTest;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use SimplyTestable\ApiBundle\Command\ScheduledJob\EnqueueCommand;

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

        $this->clearRedis();
        $this->preCall();

        $this->returnCode = $this->executeCommand($this->getCommandName(), [
            'id' => 1
        ]);
    }

    abstract protected function getExpectedReturnCode();

    protected function preCall() {}

    /**
     *
     * @return ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {
        return [
            new EnqueueCommand()
        ];
    }

    public function testReturnCode() {
        $this->assertEquals($this->getExpectedReturnCode(), $this->returnCode);
    }

}
