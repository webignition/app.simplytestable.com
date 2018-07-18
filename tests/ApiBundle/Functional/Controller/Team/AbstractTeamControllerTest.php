<?php

namespace Tests\ApiBundle\Functional\Controller\Team;

use SimplyTestable\ApiBundle\Controller\TeamController;
use SimplyTestable\ApiBundle\Entity\User;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\Controller\AbstractControllerTest;

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
