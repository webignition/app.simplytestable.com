<?php

namespace Tests\AppBundle\Functional\Controller\Team;

use AppBundle\Controller\TeamController;
use AppBundle\Entity\User;
use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\Controller\AbstractControllerTest;

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

        $this->userFactory = new UserFactory(self::$container);
        $this->users = $this->userFactory->createPublicPrivateAndTeamUserSet();
    }
}
