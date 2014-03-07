<?php

namespace SimplyTestable\ApiBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Client as HttpClient;
use Symfony\Component\Console\Tester\CommandTester;

abstract class BaseTestCase extends WebTestCase {
    
    const FIXTURES_DATA_RELATIVE_PATH = '/Fixtures/Data';

    /**
     *
     * @var Symfony\Bundle\FrameworkBundle\Client
     */
    protected $client;

    /**
     *
     * @var appTestDebugProjectContainer
     */
    protected $container;
    
    
    /**
     *
     * @var Symfony\Bundle\FrameworkBundle\Console\Application
     */
    protected $application;
       

    public function setUp() {        
        $this->client = static::createClient();
        $this->container = $this->client->getKernel()->getContainer();        
        $this->application = new Application(self::$kernel);
        $this->application->setAutoExit(false);
        
        foreach ($this->getCommands() as $command) {
            $this->application->add($command);
        }        
        
        $this->setDefaultSystemState();
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getCommands() {
        return array_merge(array(
            new \Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand(),
            new \SimplyTestable\ApiBundle\Command\Maintenance\DisableReadOnlyCommand(),
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand(),
            new \SimplyTestable\ApiBundle\Command\Job\PrepareCommand(),
            new \SimplyTestable\ApiBundle\Command\Task\Assign\Command(),
            new \SimplyTestable\ApiBundle\Command\Task\Assign\SelectedCommand(),
            new \SimplyTestable\ApiBundle\Command\Task\Assign\CollectionCommand(),
            new \SimplyTestable\ApiBundle\Command\Job\ResolveWebsiteCommand()
        ), $this->getAdditionalCommands());
    }    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */    
    protected function getAdditionalCommands() {
        return array();
    }    
    
    protected function executeCommand($name, $arguments = array()) {
        $command = $this->application->find($name);
        $commandTester = new CommandTester($command);        
        
        $arguments['command'] = $command->getName();
        
        return $commandTester->execute($arguments);
    }    
    
    protected function setDefaultSystemState() {
        $this->executeCommand('simplytestable:maintenance:disable-read-only');        
    }
    
    
    protected static function setupDatabase() {        
        $commands = array(
            'php app/console doctrine:database:drop -e test --force',
            'php app/console doctrine:database:create -e test',
            'php app/console doctrine:migrations:migrate -e test --no-interaction'
        );
        
        foreach ($commands as $command) {
            exec($command);
        }
    } 
    
    protected function loadDataFixtures() {
        $this->executeCommand('doctrine:fixtures:load', array(
            '--append' => true
        ));
    }    
    
    protected function clearRedis() {        
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
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Controller
     */
    protected function createController($controllerClass, $controllerMethod, array $postData = array(), array $queryData = array()) {
        $request = $this->createWebRequest();
        $request->attributes->set('_controller', $controllerClass.'::'.$controllerMethod);
        $request->request->add($postData);
        $request->query->add($queryData);
        $this->container->set('request', $request);
              
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
    
    private function getControllerCallable(Request $request) {
        $controllerResolver = new \Symfony\Component\HttpKernel\Controller\ControllerResolver();        
        return $controllerResolver->getController($request);                
    }

    /**
     * Creates a new Request object and hydrates it with the proper values to make
     * a valid web request.
     *
     * @return \Symfony\Component\HttpFoundation\Request The hydrated Request object.
     */
    protected function createWebRequest() {        
        $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        return $request;
    }
    
    
    /**
     *
     * @param string $testName
     * @return string
     */
    protected function getFixturesDataPath($testName = null) {
        $path = __DIR__ . self::FIXTURES_DATA_RELATIVE_PATH . '/' . str_replace('\\', DIRECTORY_SEPARATOR, get_class($this));
        
        if (!is_null($testName)) {
            $path .= '/' . $testName;
        }
        
        return $path;
    } 
    
    
    /**
     *
     * @return string
     */
    protected function getCommonFixturesDataPath() {
        return __DIR__ . self::FIXTURES_DATA_RELATIVE_PATH . '/Common';
    } 
    
    public function tearDown() {
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
