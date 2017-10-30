<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Team;

use SimplyTestable\ApiBundle\Controller\TeamController;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;

abstract class AbstractTeamControllerTest extends AbstractBaseTestCase
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

        $this->teamController = new TeamController();
        $this->teamController->setContainer($this->container);

        $this->userFactory = new UserFactory($this->container);
        $this->users = $this->userFactory->createPublicPrivateAndTeamUserSet();
    }
}
