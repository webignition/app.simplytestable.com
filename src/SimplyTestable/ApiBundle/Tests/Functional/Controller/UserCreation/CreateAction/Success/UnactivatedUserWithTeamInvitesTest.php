<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\CreateAction\Success;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class UnactivatedUserWithTeamInvitesTest extends SuccessTest {

//
//
//    public function testWithUnactivatedUserThatHasTeamInviteSetsChosenPassword() {
//        $leader = $this->createAndActivateUser('leader@example.com');
//        $this->getTeamService()->create('Foo', $leader);
//        $this->getUserService()->setUser($leader);
//
//        $this->getTeamInviteController('getAction')->getAction('user@example.com');
//
//        $tempUser = $this->getUserService()->findUserByEmail('user@example.com');
//        $tempUserPassword = $tempUser->getPassword();
//
//        $this->getUserCreationController('createAction', array(
//            'email' => rawurldecode('user@example.com'),
//            'password' => 'foobar'
//        ))->createAction();
//
//        $user = $this->getUserService()->findUserByEmail('user@example.com');
//
//        $this->assertEquals($tempUser->getId(), $user->getId());
//        $this->assertFalse($tempUserPassword == $user->getPassword());
//    }

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


    /**
     * @var int
     */
    private $tempUserId;


    /**
     * @var string
     */
    private $tempUserPassword;


    public function setUp() {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        $leader = $userFactory->createAndActivateUser('leader@example.com');
        $this->getTeamService()->create('Foo', $leader);
        $this->getUserService()->setUser($leader);

        $this->getTeamInviteController('getAction')->getAction('user@example.com');

        $tempUser = $this->getUserService()->findUserByEmail('user@example.com');
        $this->tempUserId = $tempUser->getId();
        $this->tempUserPassword = $tempUser->getPassword();

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController([
            'email' => rawurldecode('user@example.com'),
            'password' => 'foobar'
        ])->$methodName();

        $this->user = $this->getUserService()->findUserByEmail(self::DEFAULT_EMAIL);
    }


    public function testTempUserAndCreatedUserAreTheSameUser() {
        $this->assertEquals($this->tempUserId, $this->user->getId());
    }


    public function testTempUserPasswordIsNotTheSameAsCreatedUserPassword() {
        $this->assertNotEquals($this->tempUserPassword, $this->user->getPassword());
    }


    protected function getRequestPostData() {
        return [
            'email' => self::DEFAULT_EMAIL,
            'password' => self::DEFAULT_PASSWORD
        ];
    }


}

