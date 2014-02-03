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
        self::setDefaultSystemState();
        
        foreach ($this->getCommands() as $command) {
            $this->application->add($command);
        }        
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getCommands() {
        return array_merge(array(
            new \SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand(),
            new \SimplyTestable\ApiBundle\Command\JobPrepareCommand(),
            new \SimplyTestable\ApiBundle\Command\TaskAssignCommand(),
            new \SimplyTestable\ApiBundle\Command\Task\AssignSelectedCommand(),
            new \SimplyTestable\ApiBundle\Command\TaskAssignCollectionCommand(),
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
    

    protected function runConsole($command, Array $options = array()) {      
        $this->fail("Calling deprecated runConsole, use executeCommand");
        
        $args = array(
            'app/console',
            $command,
            '-e',
            'test',
            '-q',
            '-n'
        );        
        
        foreach ($options as $key => $value) {
            $args[] = $key;
            
            if (!is_null($value) && !is_bool($value)) {
                $args[] = $value;
            }
        }


        $input = new ArgvInput($args); 
        return $response = $this->application->run($input);
    }
    
    
    protected function executeCommand($name, $arguments = array()) {
        $command = $this->application->find($name);
        $commandTester = new CommandTester($command);        
        
        $arguments['command'] = $command->getName();
        
        return $commandTester->execute($arguments);
    }
    
    
    protected function resetSystemState() {
        $this->setupDatabase();
        $this->runConsole('simplytestable:maintenance:disable-read-only');        
    }
    
    
    protected static function setDefaultSystemState() {
        exec('php app/console simplytestable:maintenance:disable-read-only -e test');
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
    
    protected static function loadDataFixtures() {        
        exec('php app/console doctrine:fixtures:load -e test --append');
    }
    
    protected static function setupDatabaseIfNotExists() {        
        if (self::areDatabaseMigrationsNeeded()) {
            self::setupDatabase();
        }
    }
    
    private static function areDatabaseMigrationsNeeded() {
        $migrationStatusOutputLines = array();
        exec('php app/console doctrine:migrations:status -e test', $migrationStatusOutputLines);
        
        foreach ($migrationStatusOutputLines as $migrationStatusOutputLine) {
            if (substr_count($migrationStatusOutputLine, '>> New Migrations:')) {
                //var_dump($migrationStatusOutputLine, (int)trim(str_replace('>> Available Migrations:', '', $migrationStatusOutputLine)));
                if ((int)trim(str_replace('>> New Migrations:', '', $migrationStatusOutputLine)) > 0) {
                    return true;
                }
            }
        }
        
        return false;      
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
    
    
    protected function setHttpFixtures($fixtures) {
        $this->getHttpClientService()->reset();
        
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();        
        
        foreach ($fixtures as $fixture) {
            if ($fixture instanceof \Exception) {
                $plugin->addException($fixture);
            } else {
                $plugin->addResponse($fixture);
            }            
        }
        
        $this->getHttpClientService()->get()->addSubscriber($plugin);      
    }
    
    
    protected function getHttpFixtures($path) {
        $fixtures = array();        
        $fixturesDirectory = new \DirectoryIterator($path);
        
        $fixturePathnames = array();
        
        foreach ($fixturesDirectory as $directoryItem) {
            if ($directoryItem->isFile()) { 
                $fixturePathnames[] = $directoryItem->getPathname();
            }
        }
        
        sort($fixturePathnames);
        
        foreach ($fixturePathnames as $fixturePathname) {                        
            $fixtureContent = trim(file_get_contents($fixturePathname));
            
            switch (substr($fixtureContent, 0, 4)) {
                case 'CURL':
                    $curlException = new \Guzzle\Http\Exception\CurlException();
                    $curlException->setError('', (int)  str_replace('CURL/', '', $fixtureContent));
                    $fixtures[] = $curlException;
                    break;
                
                case 'HTTP':
                    $fixtures[] = \Guzzle\Http\Message\Response::fromMessage($fixtureContent);            
                    break;
            }
        }
        
        return $fixtures;
    }
    
    protected function getFixture($path) {
        return file_get_contents($path);
    }
    
    
    /**
     * 
     * @param array $items Collection of http messages and/or curl exceptions
     * @return array
     */
    protected function buildHttpFixtureSet($items) {
        $fixtures = array();
        
        foreach ($items as $item) {
            switch ($this->getHttpFixtureItemType($item)) {
                case 'httpMessage':
                    $fixtures[] = \Guzzle\Http\Message\Response::fromMessage($item);
                    break;
                
                case 'curlException':
                    $fixtures[] = $this->getCurlExceptionFromCurlMessage($item);                    
                    break;
                
                default:
                    throw new \LogicException();
            }
        }
        
        return $fixtures;
    }    
    
    
    /**
     * 
     * @param string $item
     * @return string
     */
    private function getHttpFixtureItemType($item) {
        if (substr($item, 0, strlen('HTTP')) == 'HTTP') {
            return 'httpMessage';
        }
        
        return 'curlException';
    }  
    
    
    /**
     * 
     * @param string $curlMessage
     * @return \Guzzle\Http\Exception\CurlException
     */
    private function getCurlExceptionFromCurlMessage($curlMessage) {
        $curlMessageParts = explode(' ', $curlMessage, 2);
        
        $curlException = new \Guzzle\Http\Exception\CurlException();
        $curlException->setError($curlMessageParts[1], (int)  str_replace('CURL/', '', $curlMessageParts[0]));
        
        return $curlException;
    }    
    

}
