<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start;

use SimplyTestable\ApiBundle\Controller\Job\StartController as JobStartController;
use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use Symfony\Component\HttpFoundation\Request;

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

    /**
     * @param Request $request
     *
     * @return JobStartController
     */
    protected function createJobStartController(Request $request)
    {
        $this->container->set('request', $request);
        $this->container->enterScope('request');

        $controller = new JobStartController();
        $controller->setContainer($this->container);

        return $controller;
    }
}
