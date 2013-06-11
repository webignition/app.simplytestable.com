<?php

namespace SimplyTestable\ApiBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Client as HttpClient;

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
    private $application;
       

    public function setUp() {        
        $this->client = static::createClient();
        $this->container = $this->client->getKernel()->getContainer();        
        $this->application = new Application(self::$kernel);
        $this->application->setAutoExit(false);
        self::setDefaultSystemState();
    }
    

    protected function runConsole($command, Array $options = array()) {
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
        return $this->application->run($input);
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
    protected function getFixturesDataPath($testName) {
        return __DIR__ . self::FIXTURES_DATA_RELATIVE_PATH . '/' . str_replace('\\', DIRECTORY_SEPARATOR, get_class($this)) . '/' . $testName;
    } 
    
    
    /**
     *
     * @return string
     */
    protected function getCommonFixturesDataPath() {
        return __DIR__ . self::FIXTURES_DATA_RELATIVE_PATH . '/Common';
    } 
    
    public function tearDown() {
        $this->container->get('doctrine')->getConnection()->close();
        parent::tearDown();
    }
    
    
    protected function setHttpFixtures($fixtures) {
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        
        foreach ($fixtures as $fixture) {
            $plugin->addResponse($fixture);
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
                $fixtures[] = \Guzzle\Http\Message\Response::fromMessage(file_get_contents($fixturePathname));            
        }
        
        return $fixtures;
    }
    

}
