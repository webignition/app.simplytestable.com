<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActivateAction\Failure;

use SimplyTestable\ApiBundle\Controller\UserCreationController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Entity\User;

abstract class FailureTest extends BaseControllerJsonTestCase {

    /**
     * @var User
     */
    private $user;

    protected function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $this->user = $userFactory->create();
    }

    abstract protected function getConfirmationToken();

    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'token' => $this->getConfirmationToken()
        ];
    }

    public function testActivateThrowsHttp400Exception() {
        $userCreationController = new UserCreationController();
        $userCreationController->setContainer($this->container);

        try {
            $userCreationController->activateAction($this->getConfirmationToken());

            $this->fail('Attempt to activate with incorrect token did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());
        }
    }

}

