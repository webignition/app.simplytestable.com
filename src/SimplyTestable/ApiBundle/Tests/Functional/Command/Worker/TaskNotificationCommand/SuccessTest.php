<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Worker\TaskNotificationCommand;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;

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

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(),
            HttpFixtureFactory::createSuccessResponse(),
            HttpFixtureFactory::createSuccessResponse(),
        ]);

        $this->workers = $this->createWorkers(3);
        $this->returnCode = $this->executeCommand($this->getCommandName());
    }


    public function testReturnCodeIsZero() {
        $this->assertEquals(0, $this->returnCode);
    }

}
