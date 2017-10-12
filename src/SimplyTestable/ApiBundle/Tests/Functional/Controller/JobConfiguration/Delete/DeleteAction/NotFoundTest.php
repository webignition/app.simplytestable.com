<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Delete\DeleteAction;

use SimplyTestable\ApiBundle\Controller\JobConfiguration\DeleteController;
use Symfony\Component\HttpFoundation\Response;

class NotFoundTest extends DeleteTest {

    /**
     * @var Response
     */
    private $response;

    protected function setUp() {
        parent::setUp();

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $controller = new DeleteController();
        $controller->setContainer($this->container);

        $this->response = $controller->deleteAction(self::LABEL);
    }

    public function testResponseStatusCode() {
        $this->assertEquals(404, $this->response->getStatusCode());
    }

}