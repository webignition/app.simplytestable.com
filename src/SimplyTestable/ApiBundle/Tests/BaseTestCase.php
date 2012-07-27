<?php

namespace SimplyTestable\ApiBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class BaseTestCase extends WebTestCase {

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

    public function setUp() {        
        $this->client = static::createClient();
        $this->container = $this->client->getKernel()->getContainer();

        $kernel = new \AppKernel("test", true);
        $kernel->boot();
        $this->_application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
        $this->_application->setAutoExit(false);
    }    

    protected function runConsole($command, Array $options = array()) {
        $options["-e"] = "test";
        $options["-n"] = null;
        $options["-q"] = null;
        $options = array_merge($options, array('command' => $command));
        
        return $this->_application->run(new \Symfony\Component\Console\Input\ArrayInput($options));
    }
    
    protected function setupDatabase() {
        $this->runConsole("doctrine:database:drop", array("--force" => true));
        $this->runConsole("doctrine:database:create");        
        exec('php app/console doctrine:migrations:migrate --no-interaction -e test');
        $this->runConsole("cache:warmup");        
    }    

    /**
     * Builds a Controller object and the request to satisfy it. Attaches the request
     * to the object and to the container.
     *
     * @param string The full path to the Controller class.
     * @param array An array of parameters to pass into the request.
     * @return \Symfony\Bundle\FrameworkBundle\Controller\Controller The built Controller object.
     */
    protected function createController($controllerClass, array $parameters = array(), array $query = array()) {
        $request = $this->createWebRequest();
        $request->request->add($parameters);
        $request->query->add($query);

        $this->container->set('request', $request);

        $controller = new $controllerClass;
        $controller->setContainer($this->container);

        return($controller);
    }

    /**
     * Creates a new Request object and hydrates it with the proper values to make
     * a valid web request.
     *
     * @return \Symfony\Component\HttpFoundation\Request The hydrated Request object.
     */
    protected function createWebRequest() {
        $request = new \Symfony\Component\HttpFoundation\Request;
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        return $request;
    }

}
