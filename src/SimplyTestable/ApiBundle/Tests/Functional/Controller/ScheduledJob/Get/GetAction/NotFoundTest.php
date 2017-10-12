<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Get\GetAction;

use SimplyTestable\ApiBundle\Controller\ScheduledJob\GetController;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\ActionTest;
use Symfony\Component\HttpFoundation\Response;

class NotFoundTest extends ActionTest {

    /**
     * @var Response
     */
    private $response;

    protected function setUp() {
        parent::setUp();

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $controller = new GetController();
        $controller->setContainer($this->container);

        $this->response = $controller->getAction('foo');

    }

    public function testResponseStatusCode() {
        $this->assertEquals(404, $this->response->getStatusCode());
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'id' => '1'
        ];
    }
}