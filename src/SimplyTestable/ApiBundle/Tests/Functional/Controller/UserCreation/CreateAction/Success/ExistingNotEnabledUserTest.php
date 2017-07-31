<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\CreateAction\Success;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class ExistingNotEnabledUserTest extends SuccessTest {

    const DEFAULT_EMAIL = 'user@example.com';
    const DEFAULT_PASSWORD = 'password';

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    private $response;


    /**
     * @var User
     */
    private $user;


    public function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $userFactory->create(self::DEFAULT_EMAIL);

        $this->assertTrue($this->getUserService()->exists(self::DEFAULT_EMAIL));

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController($this->getRequestPostData())->$methodName();
        $this->user = $this->getUserService()->findUserByEmail(self::DEFAULT_EMAIL);
    }


    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    protected function getRequestPostData() {
        return [
            'email' => self::DEFAULT_EMAIL,
            'password' => self::DEFAULT_PASSWORD
        ];
    }


}

