<?php

namespace App\Tests\Functional\Controller\TeamInvite;

use App\Controller\TeamInviteController;
use App\Entity\User;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\Controller\AbstractControllerTest;

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

        $this->userFactory = self::$container->get(UserFactory::class);
        $this->users = $this->userFactory->createPublicPrivateAndTeamUserSet();
    }
}
