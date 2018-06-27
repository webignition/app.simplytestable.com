<?php

namespace Tests\ApiBundle\Functional\Controller\TeamInvite;

use SimplyTestable\ApiBundle\Controller\TeamInviteController;
use SimplyTestable\ApiBundle\Entity\User;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\Controller\AbstractControllerTest;

abstract class AbstractTeamInviteControllerTest extends AbstractControllerTest
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

        $this->teamInviteController = $this->container->get(TeamInviteController::class);

        $this->userFactory = new UserFactory($this->container);
        $this->users = $this->userFactory->createPublicPrivateAndTeamUserSet();
    }
}
