<?php

namespace SimplyTestable\ApiBundle\Tests\Adapter\Job\TaskConfiguration\RequestAdapter\GetCollection;

use SimplyTestable\ApiBundle\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;
use SimplyTestable\ApiBundle\Tests\Adapter\Job\TaskConfiguration\RequestAdapter\AdapterTest;
use Symfony\Component\HttpFoundation\Request;

abstract class GetCollectionTest extends AdapterTest {

    /**
     * @var TaskConfigurationCollection
     */
    protected $collection;

    public function setUp() {
        parent::setUp();

        $this->getAdapter()->setRequest(new Request([], $this->getRequestValues()));
        $this->getAdapter()->setTaskTypeService($this->container->get('simplytestable.services.tasktypeservice'));
        $this->collection = $this->getAdapter()->getCollection();
    }

    abstract protected function getRequestValues();
}
