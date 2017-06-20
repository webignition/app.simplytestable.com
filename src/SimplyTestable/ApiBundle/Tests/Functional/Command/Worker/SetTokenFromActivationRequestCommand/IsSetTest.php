<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Worker\SetTokenFromActivationRequestCommand;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;

class IsSetTest extends CommandTest {

    /**
     * @var int
     */
    private $returnCode;

    /**
     * @var Worker[]
     */
    private $workers;


    /**
     * @var WorkerActivationRequest[]
     */
    private $activationRequests;

    public function setUp() {
        parent::setUp();

        $this->workers = $this->createWorkers(3);

        foreach ($this->workers as $worker) {
            $this->activationRequests[$worker->getHostname()] = $this->getWorkerActivationRequestService()->create($worker, $worker->getHostname() . '.activation-token');
        }

        $this->returnCode = $this->executeCommand($this->getCommandName());
    }


    public function testReturnCodeIsZero() {
        $this->assertEquals(0, $this->returnCode);
    }


    public function testTokenIsSet() {
        foreach ($this->workers as $worker) {
            $this->assertNotNull($worker->getToken());
        }
    }


    public function testTokenMatchesActivationRequest() {
        foreach ($this->workers as $worker) {
            $this->assertEquals($this->getWorkerActivationRequestService()->fetch($worker)->getToken(), $worker->getToken());
        }
    }

}
