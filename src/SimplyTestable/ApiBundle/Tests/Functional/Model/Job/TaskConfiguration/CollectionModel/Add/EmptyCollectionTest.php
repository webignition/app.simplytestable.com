<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Model\Job\TaskConfiguration\CollectionModel\Add;

use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class EmptyCollectionTest extends AddTest {

    /**
     * @var TaskConfiguration
     */
    private $taskConfiguration;


    protected function setUp() {
        parent::setUp();

        $taskType = new TaskType();
        $taskType->setName('foo');

        $this->taskConfiguration = new TaskConfiguration();
        $this->taskConfiguration->setType($taskType);
        $this->taskConfiguration->setOptions([]);

        $this->getInstance()->add(
            $this->taskConfiguration
        );
    }

    public function testCollectionSize() {
        $this->assertEquals(1, count($this->getInstance()->get()));
    }


    public function testExpectedTaskConfigurationIsInCollection() {
        $this->assertEquals(
            spl_object_hash($this->taskConfiguration),
            spl_object_hash($this->getInstance()->get()[0])
        );
    }

}
