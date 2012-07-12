<?php

namespace SimplyTestable\ApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BaseControllerTestCase extends WebTestCase {

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

        return $request ;
    }

}
