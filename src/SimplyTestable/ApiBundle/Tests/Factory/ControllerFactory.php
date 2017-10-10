<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Controller\Job\StartController as JobStartController;
use SimplyTestable\ApiBundle\Controller\TaskController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ControllerFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     *
     * @return Controller
     */
    public function createController($className, Request $request)
    {
        $this->container->set('request', $request);
        $this->container->enterScope('request');

        /* @var Controller $controller */
        $controller = new $className;
        $controller->setContainer($this->container);

        return $controller;
    }

    /**
     * @param Request $request
     *
     * @return TaskController
     */
    public function createTaskController(Request $request)
    {
        /* @var TaskController $taskController */
        $taskController = $this->createController(TaskController::class, $request);

        return $taskController;
    }
}
