<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task;

use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractTaskControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @param Request $request
     */
    protected function addRequestToContainer(Request $request)
    {
        $this->container->set('request', $request);
        $this->container->enterScope('request');
    }

    /**
     * @return TaskController
     */
    protected function createTaskController()
    {
        $controller = new TaskController();
        $controller->setContainer($this->container);

        return $controller;
    }
}
