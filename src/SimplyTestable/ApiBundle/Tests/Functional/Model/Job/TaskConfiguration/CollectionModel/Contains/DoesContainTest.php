<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Model\Job\TaskConfiguration\CollectionModel\Contains;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class DoesContainTest extends ContainsTest {

    /**
     * @var TaskConfiguration
     */
    private $taskConfiguration1;

    /**
     * @var TaskConfiguration
     */
    private $taskConfiguration2;

    protected function setUp() {
        parent::setUp();

        $taskType1 = new TaskType();
        $taskType1->setName('foo1');

        $taskType2 = new TaskType();
        $taskType2->setName('foo2');

        $this->taskConfiguration1 = new TaskConfiguration();
        $this->taskConfiguration1->setType($taskType1);
        $this->taskConfiguration1->setOptions([]);

        $this->taskConfiguration2 = new TaskConfiguration();
        $this->taskConfiguration2->setType($taskType2);
        $this->taskConfiguration2->setOptions([]);

        $taskConfiguration3 = new TaskConfiguration();
        $taskConfiguration3->setType($taskType2);
        $taskConfiguration3->setOptions(['foo' => 'bar']);

        $this->getInstance()->add($this->taskConfiguration1);
        $this->getInstance()->add($this->taskConfiguration2);
        $this->getInstance()->add($taskConfiguration3);
    }


    public function testContainsTaskConfiguration1() {
        $this->assertTrue($this->getInstance()->contains($this->taskConfiguration1));
    }


    public function testContainsTaskConfiguration2() {
        $this->assertTrue($this->getInstance()->contains($this->taskConfiguration2));
    }


}
