<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Model\Job\TaskConfiguration\CollectionModel\Contains;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class DoesNotContainTest extends ContainsTest {

    /**
     * @var TaskConfiguration
     */
    private $taskConfiguration1;


    public function setUp() {
        parent::setUp();

        $taskType1 = new TaskType();
        $taskType1->setName('foo1');

        $taskType2 = new TaskType();
        $taskType2->setName('foo2');

        $this->taskConfiguration1 = new TaskConfiguration();
        $this->taskConfiguration1->setType($taskType1);
        $this->taskConfiguration1->setOptions([]);

        $taskConfiguration2 = new TaskConfiguration();
        $taskConfiguration2->setType($taskType2);
        $taskConfiguration2->setOptions([]);

        $taskConfiguration3 = new TaskConfiguration();
        $taskConfiguration3->setType($taskType2);
        $taskConfiguration3->setOptions(['foo' => 'bar']);

        $this->getInstance()->add($taskConfiguration2);
        $this->getInstance()->add($taskConfiguration3);
    }


    public function testDoesNotContainTaskConfiguration1() {
        $this->assertFalse($this->getInstance()->contains($this->taskConfiguration1));
    }

}
