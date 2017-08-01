<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Model\Job\TaskConfiguration\CollectionModel\Add;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class DuplicateTest extends AddTest {

    /**
     * @var TaskConfiguration
     */
    private $taskConfiguration;


    protected function setUp() {
        parent::setUp();

        $taskType1 = new TaskType();
        $taskType1->setName('foo1');

        $taskType2 = new TaskType();
        $taskType2->setName('foo2');

        $taskConfiguration1 = new TaskConfiguration();
        $taskConfiguration1->setType($taskType1);
        $taskConfiguration1->setOptions([]);

        $taskConfiguration2 = new TaskConfiguration();
        $taskConfiguration2->setType($taskType2);
        $taskConfiguration2->setOptions([]);

        $this->taskConfiguration = new TaskConfiguration();
        $this->taskConfiguration->setType($taskType1);
        $this->taskConfiguration->setOptions([]);

        $this->getInstance()->add($taskConfiguration1);
        $this->getInstance()->add($taskConfiguration2);

        $this->getInstance()->add(
            $this->taskConfiguration
        );
    }

    public function testCollectionSize() {
        $this->assertEquals(2, count($this->getInstance()->get()));
    }


    public function testExpectedTaskConfigurationIsNotInCollection() {
        foreach ($this->getInstance()->get() as $taskConfiguration) {
            $this->assertNotEquals(
                spl_object_hash($taskConfiguration),
                spl_object_hash($this->taskConfiguration)
            );
        }
    }

}
