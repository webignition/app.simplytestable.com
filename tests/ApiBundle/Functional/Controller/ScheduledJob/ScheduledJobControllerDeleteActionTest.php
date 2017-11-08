<?php

namespace Tests\ApiBundle\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ScheduledJobController;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;
use Tests\ApiBundle\Factory\JobConfigurationFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduledJobControllerDeleteActionTest extends AbstractBaseTestCase
{
    /**
     * @var ScheduledJobController
     */
    private $scheduledJobController;

    /**
     * @var ScheduledJob
     */
    private $scheduledJob;

    /**
     * @var User
     */
    private $user;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->scheduledJobController = new ScheduledJobController();
        $this->scheduledJobController->setContainer($this->container);

        $userFactory = new UserFactory($this->container);
        $this->user = $userFactory->createAndActivateUser();

        $this->setUser($this->user);

        $jobConfigurationFactory = new JobConfigurationFactory($this->container);
        $jobConfiguration = $jobConfigurationFactory->create([
            JobConfigurationFactory::KEY_USER => $this->user,
        ]);

        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $this->scheduledJob = $scheduledJobService->create($jobConfiguration);
    }

    public function testDeleteActionGetRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_delete', [
            'id' => 0,
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testDeleteActionPostRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_delete', [
            'id' => $this->scheduledJob->getId(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $this->user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testDeleteActionScheduledJobNotFound()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->scheduledJobController->deleteAction(0);
    }

    public function testDeleteSuccess()
    {
        $scheduledJobRepository = $this->container->get('simplytestable.repository.scheduledjob');

        $scheduledJobId = $this->scheduledJob->getId();

        $response = $this->scheduledJobController->deleteAction($this->scheduledJob->getId());

        $this->assertTrue($response->isSuccessful());

        $scheduledJob = $scheduledJobRepository->find($scheduledJobId);
        $this->assertNull($scheduledJob);
    }
}
