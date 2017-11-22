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
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractJobControllerTest extends AbstractBaseTestCase
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
            $this->container->get('router'),
            $this->container->get(RetrievalService::class),
            $this->container->get('doctrine.orm.entity_manager')
        );

        $this->userFactory = new UserFactory($this->container);
        $this->jobFactory = new JobFactory($this->container);
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
            $this->container->get(UserService::class),
            $user,
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );
    }
}
