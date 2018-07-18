<?php

namespace Tests\AppBundle\Functional\Controller\Job\Job;

use AppBundle\Controller\Job\JobController;
use AppBundle\Entity\Job\Job;
use AppBundle\Entity\User;
use AppBundle\Services\Job\RetrievalService;
use AppBundle\Services\UserService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Factory\JobFactory;
use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\Controller\AbstractControllerTest;

abstract class AbstractJobControllerTest extends AbstractControllerTest
{
    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @var JobFactory
     */
    protected $jobFactory;

    /**
     * @var JobController
     */
    protected $jobController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobController = new JobController(
            self::$container->get('router'),
            self::$container->get(RetrievalService::class),
            self::$container->get('doctrine.orm.entity_manager')
        );

        $this->userFactory = new UserFactory(self::$container);
        $this->jobFactory = new JobFactory(self::$container);
    }

    /**
     * @param User $user
     * @param Job $job
     *
     * @return RedirectResponse|Response
     */
    protected function callSetPublicAction(User $user, Job $job)
    {
        return $this->jobController->setPublicAction(
            self::$container->get(UserService::class),
            $user,
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );
    }
}
