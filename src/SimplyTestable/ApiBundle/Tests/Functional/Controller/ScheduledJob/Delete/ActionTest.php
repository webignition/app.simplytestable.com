<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Delete;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\ActionTest as BaseActionTest;

abstract class ActionTest extends BaseActionTest {


    /**
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\ScheduledJob\DeleteController
     */
    protected function getCurrentController(array $postData = [], array $queryData = []) {
        return parent::getCurrentController($postData, $queryData);
    }


}