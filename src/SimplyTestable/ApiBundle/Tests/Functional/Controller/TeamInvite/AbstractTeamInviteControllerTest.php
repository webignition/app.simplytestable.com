<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\TeamInvite;

use SimplyTestable\ApiBundle\Controller\TeamInviteController;
use SimplyTestable\ApiBundle\Entity\Team\Invite;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractTeamInviteControllerTest extends AbstractBaseTestCase
{
    /**
     * @var TeamInviteController
     */
    protected $teamInviteController;

    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @var User[]
     */
    protected $users;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->teamInviteController = new TeamInviteController();
        $this->teamInviteController->setContainer($this->container);

        $this->userFactory = new UserFactory($this->container);
        $this->users = $this->userFactory->createPublicPrivateAndTeamUserSet();
    }
}
