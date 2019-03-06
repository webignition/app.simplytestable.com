<?php

namespace App\Tests\Functional\Controller\Team;

use App\Controller\TeamController;
use App\Entity\User;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\Controller\AbstractControllerTest;

abstract class AbstractTeamControllerTest extends AbstractControllerTest
{
    /**
     * @var TeamController
     */
    protected $teamController;

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

        $this->teamController = self::$container->get(TeamController::class);

        $this->userFactory = self::$container->get(UserFactory::class);
        $this->users = $this->userFactory->createPublicPrivateAndTeamUserSet();
    }
}
