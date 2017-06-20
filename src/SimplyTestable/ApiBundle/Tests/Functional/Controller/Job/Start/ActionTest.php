<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Start;

use SimplyTestable\ApiBundle\Controller\Job\StartController as JobStartController;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

abstract class ActionTest extends BaseControllerJsonTestCase
{
    /**
     * @param array $postData
     * @param array $queryData
     *
     * @return JobStartController
     */
    protected function getCurrentController(array $postData = [], array $queryData = [])
    {
        return parent::getCurrentController($postData, $queryData);
    }
}
