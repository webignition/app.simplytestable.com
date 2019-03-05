<?php

namespace App\Tests\Functional\Controller\Job\Job;

use App\Controller\Job\JobController;
use App\Entity\Job\Job;
use App\Entity\User;
use App\Repository\JobRepository;
use App\Repository\TaskRepository;
use App\Services\Job\RetrievalService;
use App\Services\UserService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Functional\Controller\AbstractControllerTest;

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
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get(JobRepository::class),
            self::$container->get(TaskRepository::class)
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
