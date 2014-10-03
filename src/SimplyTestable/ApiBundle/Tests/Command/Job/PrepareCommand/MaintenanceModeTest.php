<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand;

class MaintenanceModeTest extends CommandTest {

    /**
     * @var int
     */
    private $returnCode;

    public function setUp() {
        parent::setUp();

        $this->clearRedis();

        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->returnCode = $this->executeCommand($this->getCommandName(), [
            'id' => 1
        ]);
    }

    
    public function testReturnCode() {
        $this->assertEquals(2, $this->returnCode);
    }

    public function testResqueJobIsRequeued() {
        $this->assertTrue($this->getResqueQueueService()->contains('job-prepare', [
            'id' => 1
        ]));
    }
}
