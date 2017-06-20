<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\JobConfiguration\Update\UpdateAction;

use Symfony\Component\HttpFoundation\Response;

class NotFoundTest extends UpdateTest {

    /**
     * @var Response
     */
    private $response;

    public function setUp() {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName(
            $this->container->get('request'),
            self::LABEL
        );
    }

    public function testResponseStatusCode() {
        $this->assertEquals(404, $this->response->getStatusCode());
    }
}