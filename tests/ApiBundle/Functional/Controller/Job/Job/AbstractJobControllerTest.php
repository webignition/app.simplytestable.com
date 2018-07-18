<?php

namespace Tests\ApiBundle\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Controller\Job\JobController;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\Job\RetrievalService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\Controller\AbstractControllerTest;

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
