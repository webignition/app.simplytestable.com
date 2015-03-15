<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\GetList;

use SimplyTestable\ApiBundle\Tests\Controller\ScheduledJob\ActionTest as BaseActionTest;

abstract class ActionTest extends BaseActionTest {


    /**
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\ScheduledJob\GetListController
     */
    protected function getCurrentController(array $postData = [], array $queryData = []) {
        return parent::getCurrentController($postData, $queryData);
    }


}