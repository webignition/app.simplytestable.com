<?php

namespace SimplyTestable\ApiBundle\Tests\Adapter\Job\TaskConfiguration\RequestAdapter\GetCollection;

class TestTest extends GetCollectionTest {

    protected function getRequestValues() {
        return [
            'task-configuration' => [
                'HTML validation' => [],
                'CSS validation' => []
            ]
        ];
    }


    public function testTaskConfigurationCollectionSize() {
        $this->assertEquals(2, $this->collection->count());
    }


    public function testHtmlValidationTypeIsPresent() {
        $this->assertEquals(
            $this->container->get('simplytestable.services.tasktypeservice')->getByName('HTML validation'),
            $this->collection->get()[0]->getType()
        );
    }


    public function testCssValidationTypeIsPresent() {
        $this->assertEquals(
            $this->container->get('simplytestable.services.tasktypeservice')->getByName('CSS validation'),
            $this->collection->get()[1]->getType()
        );
    }

}
