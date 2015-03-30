<?php

namespace SimplyTestable\ApiBundle\Tests\Adapter\Job\TaskConfiguration\RequestAdapter\GetCollection;

class TaskConfigurationIsNotEnabledTest extends GetCollectionTest {

    protected function getRequestValues() {
        return [
            'task-configuration' => [
                'HTML validation' => [
                    'is-enabled' => false
                ],
                'CSS validation' => [
                    'is-enabled' => false
                ]
            ]
        ];
    }

    public function testTaskConfigurationCollectionSize() {
        $this->assertEquals(2, $this->collection->count());
    }


    public function testTaskConfigurationCollectionIsEnabled() {
        foreach ($this->collection->get() as $taskConfiguration) {
            $this->assertFalse($taskConfiguration->getIsEnabled());
        }
    }

}
