<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign\CollectionCommand;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Worker;

class SpecificWorkerTest extends CollectionCommandTest {

    /**
     * @var int[]
     */
    private $taskIds = [];


    /**
     * @var Job
     */
    private $job;


    /**
     * @var Worker
     */
    private $worker;


    private $executeReturnCode = null;

    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());

        $this->queueHttpFixtures($this->buildHttpFixtureSet([
            'HTTP/1.1 200 OK
Content-Type: application/json

[{"id":1,"url":"http:\/\/webignition.net\/","state":"queued","type":"HTML validation","parameters":""},{"id":2,"url":"http:\/\/webignition.net\/articles\/","state":"queued","type":"HTML validation","parameters":""},{"id":3,"url":"http:\/\/webignition.net\/articles\/i-make-the-internet\/","state":"queued","type":"HTML validation","parameters":""},{"id":4,"url":"http:\/\/webignition.net\/articles\/getting-to-building-simpytestable-dot-com\/","state":"queued","type":"HTML validation","parameters":""},{"id":5,"url":"http:\/\/webignition.net\/articles\/veenus-group-seeks-plutonium-eating-martian-superhero\/","state":"queued","type":"HTML validation","parameters":""},{"id":6,"url":"http:\/\/webignition.net\/articles\/archive\/","state":"queued","type":"HTML validation","parameters":""},{"id":7,"url":"http:\/\/webignition.net\/articles\/program-code-is-for-people-not-computers\/","state":"queued","type":"HTML validation","parameters":""},{"id":8,"url":"http:\/\/webignition.net\/articles\/making-password-resets-60-percent-easier\/","state":"queued","type":"HTML validation","parameters":""},{"id":9,"url":"http:\/\/webignition.net\/articles\/which-is-faster-delay-perfeception-tests\/","state":"queued","type":"HTML validation","parameters":""}]'
        ]));

        $this->createWorker();
        $this->worker = $this->createWorker('worker.example.com');
        $this->createWorker();
        $this->createWorker();

        $this->taskIds = $this->getTaskIds($this->job);

        $this->executeReturnCode = $this->execute([
            'ids' => implode($this->taskIds, ','),
            'worker' => $this->worker->getHostname()
        ]);
    }


    public function testExecuteReturnCodeIs0() {
        $this->assertEquals(0, $this->executeReturnCode);
    }


    public function testTestWorker() {
        foreach ($this->job->getTasks() as $task) {
            $this->assertEquals($this->worker->getHostname(), $task->getWorker()->getHostname());
        }
    }

}