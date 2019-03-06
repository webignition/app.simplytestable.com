<?php

namespace App\Tests\Functional\Controller\Job\Job;

use App\Controller\Job\JobController;
use App\Entity\Job\Job;
use App\Entity\User;
use App\Services\UserService;
use App\Tests\Services\JobFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Services\UserFactory;
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

        $this->jobController = self::$container->get(JobController::class);
        $this->userFactory = self::$container->get(UserFactory::class);
        $this->jobFactory = self::$container->get(JobFactory::class);
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
