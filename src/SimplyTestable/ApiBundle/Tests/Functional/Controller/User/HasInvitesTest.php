<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class HasInvitesTest extends BaseControllerJsonTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
    }

    public function testNonexistentUserHasNoInvites()
    {
        try {
            $controller = $this->getUserController('hasInvitesAction');
            $controller->hasInvitesAction('user@example.com');
            $this->fail('Attempt to check for invites for non-existent user did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testUserWithNoInvitesHasNoInvites()
    {
        $user = $this->userFactory->createAndActivateUser();

        try {
            $controller = $this->getUserController('hasInvitesAction');
            $controller->hasInvitesAction($user->getEmail());
            $this->fail('Attempt to check for invites for user with no invites did not generate HTTP 404');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertEquals(404, $exception->getStatusCode());
        }
    }

    public function testUserWithInvites()
    {
        $leader = $this->userFactory->createAndActivateUser('leader@example.com');
        $user = $this->userFactory->create();

        $this->getTeamService()->create('Foo', $leader);
        $this->getTeamInviteService()->get($leader, $user);

        $this->assertEquals(
            200,
            $this->getUserController('hasInvitesAction')->hasInvitesAction($user->getEmail())->getStatusCode()
        );
    }
}
