<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob;

use SimplyTestable\ApiBundle\Controller\ScheduledJob\DeleteController;
use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\JobConfigurationFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ScheduledJobDeleteControllerTest extends AbstractBaseTestCase
{
    /**
     * @var DeleteController
     */
    private $scheduledJobDeleteController;

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

        $this->scheduledJobDeleteController = new DeleteController();
        $this->scheduledJobDeleteController->setContainer($this->container);

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

    public function testGetRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_delete_delete', [
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

    public function testPostRequest()
    {
        $router = $this->container->get('router');
        $requestUrl = $router->generate('scheduledjob_delete_delete', [
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
        $this->setExpectedException(NotFoundHttpException::class);

        $this->scheduledJobDeleteController->deleteAction(0);
    }

    public function testDeleteSuccess()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $scheduledJobId = $this->scheduledJob->getId();

        $response = $this->scheduledJobDeleteController->deleteAction($this->scheduledJob->getId());

        $this->assertTrue($response->isSuccessful());

        $scheduledJobRepository = $entityManager->getRepository(ScheduledJob::class);

        $scheduledJob = $scheduledJobRepository->find($scheduledJobId);
        $this->assertNull($scheduledJob);
    }
}
