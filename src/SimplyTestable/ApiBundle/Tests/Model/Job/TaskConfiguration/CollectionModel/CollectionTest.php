<?php

namespace SimplyTestable\ApiBundle\Tests\Model\Job\TaskConfiguration\CollectionModel;

use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Tests\Model\ModelTest;

abstract class CollectionTest extends ModelTest {


    /**
     * @var TaskConfigurationCollection
     */
    protected $collection;


    public function setUp() {
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
