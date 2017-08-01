<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Worker\TaskNotificationCommand;

use SimplyTestable\ApiBundle\Entity\Worker;

class SuccessTest extends CommandTest {

    /**
     * @var int
     */
    private $returnCode;

    /**
     * @var Worker[]
     */
    private $workers;


    protected function setUp() {
        parent::setUp();

        $this->queueHttpFixtures($this->buildHttpFixtureSet([
            'HTTP/1.1 200 OK',
            'HTTP/1.1 200 OK',
            'HTTP/1.1 200 OK'
        ]));

        $this->workers = $this->createWorkers(3);
        $this->returnCode = $this->executeCommand($this->getCommandName());
    }


    public function testReturnCodeIsZero() {
        $this->assertEquals(0, $this->returnCode);
    }

}
