<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActivateAction\Failure;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\ActionTest;
use SimplyTestable\ApiBundle\Entity\User;

abstract class FailureTest extends ActionTest {

    /**
     * @var User
     */
    private $user;

    public function setUp() {
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
        try {
            $methodName = $this->getActionNameFromRouter();
            $this->getCurrentController()->$methodName($this->getConfirmationToken());
            $this->fail('Attempt to activate with incorrect token did not generate HTTP 400');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(400, $exception->getStatusCode());
        }
    }

}

