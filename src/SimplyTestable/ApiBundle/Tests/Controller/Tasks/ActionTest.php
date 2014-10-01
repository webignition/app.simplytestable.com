<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Tasks;

use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;

abstract class ActionTest extends BaseControllerJsonTestCase {


    /**
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\TasksController
     */
    protected function getCurrentController(array $postData = [], array $queryData = []) {
        return parent::getCurrentController($postData, $queryData);
    }

}