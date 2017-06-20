<?php

namespace SimplyTestable\ApiBundle\Tests\Functional;

use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use SimplyTestable\ApiBundle\Command\Job\PrepareCommand;
use SimplyTestable\ApiBundle\Command\Job\ResolveWebsiteCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Task\Assign\CollectionCommand;
use SimplyTestable\ApiBundle\Command\Task\Assign\SelectedCommand;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Console\Tester\CommandTester;

abstract class BaseTestCase extends WebTestCase
{
    const FIXTURES_DATA_RELATIVE_PATH = '/Fixtures/Data';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Application
     */
    protected $application;

    public function setUp()
    {
        $this->client = static::createClient();
        $this->container = $this->client->getKernel()->getContainer();
        $this->application = new Application(self::$kernel);
        $this->application->setAutoExit(false);

        foreach ($this->getCommands() as $command) {
            $this->application->add($command);
        }

        $this->executeCommand('simplytestable:maintenance:disable-read-only');
        $this->container->get('doctrine')->getConnection()->beginTransaction();
    }

    /**
     * @return ContainerAwareCommand[]
     */
    protected function getCommands()
    {
        return array_merge(array(
            new LoadDataFixturesDoctrineCommand(),
            new DisableReadOnlyCommand(),
            new EnableReadOnlyCommand(),
            new PrepareCommand(),
            new SelectedCommand(),
            new CollectionCommand(),
            new ResolveWebsiteCommand()
        ), $this->getAdditionalCommands());
    }

    /**
     * @return ContainerAwareCommand[]
     */
    protected function getAdditionalCommands()
    {
        return array();
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return int
     */
    protected function executeCommand($name, $arguments = array())
    {
        $command = $this->application->find($name);
        $commandTester = new CommandTester($command);

        $arguments['command'] = $command->getName();

        return $commandTester->execute($arguments);
    }

    protected function clearRedis()
    {
        exec('redis-cli -r 1 flushall');
    }

    /**
     *
     * Builds a Controller object and the request to satisfy it. Attaches the request
     * to the object and to the container.
     *
     * @param string $controllerClass The full path to the controller class
     * @param string $controllerMethod Name of the controller method to be called
     * @param array $postData Array of post values
     * @param array $queryData Array of query string values
     *
     * @return Controller
     */
    protected function createController(
        $controllerClass,
        $controllerMethod,
        array $postData = array(),
        array $queryData = array()
    ) {
        $request = $this->createWebRequest();
        $request->attributes->set('_controller', $controllerClass.'::'.$controllerMethod);
        $request->request->add($postData);
        $request->query->add($queryData);
        $this->container->set('request', $request);
        $this->container->enterScope('request');

        $controllerCallable = $this->getControllerCallable($request);
        $controllerCallable[0]->setContainer($this->container);

        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch('kernel.controller', new \Symfony\Component\HttpKernel\Event\FilterControllerEvent(
                self::$kernel,
                $controllerCallable,
                $request,
                HttpKernelInterface::MASTER_REQUEST
        ));

        return $controllerCallable[0];
    }

    /**
     * @param Request $request
     *
     * @return callable|false
     */
    private function getControllerCallable(Request $request)
    {
        $controllerResolver = new ControllerResolver();

        return $controllerResolver->getController($request);
    }

    /**
     * Creates a new Request object and hydrates it with the proper values to make
     * a valid web request.
     *
     * @return Request
     */
    protected function createWebRequest()
    {
        $request = Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        return $request;
    }

    /**
     * @param string $testName
     *
     * @return string
     */
    protected function getFixturesDataPath($testName = null)
    {
        $path = str_replace('/Functional', '', __DIR__)
            . self::FIXTURES_DATA_RELATIVE_PATH
            . '/'
            . str_replace('\\', DIRECTORY_SEPARATOR, get_class($this));

        if (!is_null($testName)) {
            $path .= '/' . $testName;
        }

        return $path;
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->container->get('doctrine')->getConnection()->close();

        $refl = new \ReflectionObject($this);
        foreach ($refl->getProperties() as $prop) {
            if (!$prop->isStatic() && 0 !== strpos($prop->getDeclaringClass()->getName(), 'PHPUnit_')) {
                $prop->setAccessible(true);
                $prop->setValue($this, null);
            }
        }
    }
}
