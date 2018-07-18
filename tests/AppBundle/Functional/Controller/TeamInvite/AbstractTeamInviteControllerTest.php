<?php

namespace Tests\AppBundle\Functional\Controller\TeamInvite;

use AppBundle\Controller\TeamInviteController;
use AppBundle\Entity\User;
use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\Controller\AbstractControllerTest;

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

        $this->teamInviteController = self::$container->get(TeamInviteController::class);

        $this->userFactory = new UserFactory(self::$container);
        $this->users = $this->userFactory->createPublicPrivateAndTeamUserSet();
    }
}
