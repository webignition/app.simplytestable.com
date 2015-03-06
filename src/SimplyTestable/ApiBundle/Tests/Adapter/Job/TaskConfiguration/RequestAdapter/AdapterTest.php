<?php

namespace SimplyTestable\ApiBundle\Tests\Adapter\Job\TaskConfiguration\RequestAdapter;

use SimplyTestable\ApiBundle\Tests\BaseTestCase;
use SimplyTestable\ApiBundle\Adapter\Job\TaskConfiguration\RequestAdapter;

abstract class AdapterTest extends BaseTestCase {

    /**
     * @var RequestAdapter
     */
    private $adapter;


    /**
     * @return RequestAdapter
     */
    public function getAdapter() {
        if (is_null($this->adapter)) {
            $this->adapter = new RequestAdapter();
        }

        return $this->adapter;
    }

}
