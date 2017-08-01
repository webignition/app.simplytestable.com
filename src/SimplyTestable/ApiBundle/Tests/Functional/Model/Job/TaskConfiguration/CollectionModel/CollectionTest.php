<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Model\Job\TaskConfiguration\CollectionModel;

use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Tests\Functional\Model\ModelTest;

abstract class CollectionTest extends ModelTest {


    /**
     * @var TaskConfigurationCollection
     */
    protected $collection;


    protected function setUp() {
        parent::setUp();
    }


    /**
     * @return TaskConfigurationCollection
     */
    protected function getInstance() {
        if (is_null($this->collection)) {
            $this->collection = parent::getInstance();
        }

        return $this->collection;
    }

}
