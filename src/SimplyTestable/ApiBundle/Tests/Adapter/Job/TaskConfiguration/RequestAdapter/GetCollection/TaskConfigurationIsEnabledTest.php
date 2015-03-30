<?php

namespace SimplyTestable\ApiBundle\Tests\Adapter\Job\TaskConfiguration\RequestAdapter\GetCollection;

class TaskConfigurationIsEnabledTest extends GetCollectionTest {

    protected function getRequestValues() {
        return [
            'task-configuration' => [
                'HTML validation' => [
                    'is-enabled' => true
                ],
                'CSS validation' => [
                    'is-enabled' => true
                ]
            ]
        ];
    }

    public function testTaskConfigurationCollectionSize() {
        $this->assertEquals(2, $this->collection->count());
    }


    public function testTaskConfigurationCollectionIsEnabled() {
        foreach ($this->collection->get() as $taskConfiguration) {
            $this->assertTrue($taskConfiguration->getIsEnabled());
        }
    }


    public function testTaskConfigurationCollectionDoesNotHaveIsEnabledOption() {
        foreach ($this->collection->get() as $taskConfiguration) {
            $this->assertEquals([], $taskConfiguration->getOptions());
        }
    }

}
