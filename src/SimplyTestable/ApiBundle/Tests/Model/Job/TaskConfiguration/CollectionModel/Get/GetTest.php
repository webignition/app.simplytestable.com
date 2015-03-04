<?php

namespace SimplyTestable\ApiBundle\Tests\Model\Job\TaskConfiguration\CollectionModel\Get;

use SimplyTestable\ApiBundle\Tests\Model\Job\TaskConfiguration\CollectionModel\CollectionTest;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class GetTest extends CollectionTest {

    const COLLECTION_SIZE = 5;

    /**
     * @var TaskConfiguration[]
     */
    private $taskConfigurations = [];


    public function setUp() {
        parent::setUp();

        for ($index = 0; $index < self::COLLECTION_SIZE; $index++) {
            $taskType = new TaskType();
            $taskType->setName('foo' . $index);

            $taskConfiguration = new TaskConfiguration();
            $taskConfiguration->setType($taskType);
            $taskConfiguration->setOptions([]);

            $this->taskConfigurations[] = $taskConfiguration;
            $this->getInstance()->add($taskConfiguration);
        }
    }


    public function testCollectionMatchesExpected() {
        $collection = $this->getInstance()->get();

        foreach ($collection as $index => $taskConfiguration) {
            $this->assertEquals(
                spl_object_hash($taskConfiguration),
                spl_object_hash($this->taskConfigurations[$index])
            );
        }
    }

}
