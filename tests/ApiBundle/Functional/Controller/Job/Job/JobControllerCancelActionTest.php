<?php

namespace Tests\ApiBundle\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Tests\ApiBundle\Factory\JobFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class JobControllerCancelActionTest extends AbstractJobControllerTest
{
    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_job_cancel', [
                'test_id' => $job->getId(),
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCancelActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        try {
            $this->jobController->cancelAction('foo', 1);
            $this->fail('ServiceUnavailableHttpException not thrown');
        } catch (ServiceUnavailableHttpException $serviceUnavailableHttpException) {
            $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
        }
    }

    /**
     * @dataProvider cancelActionDataProvider
     *
     * @param string $owner
     * @param string $requester
     * @param int $expectedResponseStatusCode
     */
    public function testCancelAction($owner, $requester, $expectedResponseStatusCode)
    {
        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $ownerUser = $users[$owner];
        $requesterUser = $users[$requester];

        $this->setUser($ownerUser);

        $canonicalUrl = 'http://example.com/';

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_USER => $ownerUser,
            JobFactory::KEY_TASKS => [
                [
                    JobFactory::KEY_TASK_STATE => TaskService::IN_PROGRESS_STATE,
                ],
            ],
        ]);

        $this->setUser($requesterUser);

        $response = $this->jobController->cancelAction($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertEquals($expectedResponseStatusCode, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function cancelActionDataProvider()
    {
        return [
            'public owner, public requester' => [
                'owner' => 'public',
                'requester' => 'public',
                'expectedStatusCode' => 200,
            ],
            'private owner, public requester' => [
                'owner' => 'private',
                'requester' => 'public',
                'expectedStatusCode' => 403,
            ],
        ];
    }

    public function testCancelCrawlJob()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $jobFailedNoSitemapState = $stateService->get(JobService::FAILED_NO_SITEMAP_STATE);
        $jobCancelledState = $stateService->get(JobService::CANCELLED_STATE);
        $jobQueuedState = $stateService->get(JobService::QUEUED_STATE);

        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $parentJob = $this->jobFactory->create([
            JobFactory::KEY_TEST_TYPES => ['CSS validation', 'JS static analysis'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'CSS validation' => ['ignore-common-cdns' => 1],
                'JS static analysis' => ['ignore-common-cdns' => 1]
            ],
            JobFactory::KEY_USER => $user,
        ]);

        $parentJob->setState($jobFailedNoSitemapState);

        $entityManager->persist($parentJob);
        $entityManager->flush();

        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');
        $crawlJobContainer = $crawlJobContainerService->getForJob($parentJob);
        $crawlJobContainerService->prepare($crawlJobContainer);
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $response = $this->jobController->cancelAction($crawlJob->getWebsite()->getCanonicalUrl(), $crawlJob->getId());

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals((string)$jobCancelledState, (string)$crawlJob->getState());
        $this->assertEquals((string)$jobQueuedState, (string)$parentJob->getState());

        /* @var Task $cssValidationTask */
        /* @var Task $jsStaticAnalysisTask */
        $cssValidationTask = null;
        $jsStaticAnalysisTask = null;

        foreach ($parentJob->getTasks() as $currentTask) {
            if (empty($cssValidationTask) && $currentTask->getType()->getName() == 'CSS validation') {
                $cssValidationTask = $currentTask;
            }

            if (empty($jsStaticAnalysisTask) && $currentTask->getType()->getName() == 'JS static analysis') {
                $jsStaticAnalysisTask = $currentTask;
            }
        }

        $this->assertEquals(
            $this->container->getParameter('css_validation_domains_to_ignore'),
            json_decode($cssValidationTask->getParameters())->{'domains-to-ignore'}
        );

        $this->assertEquals(
            $this->container->getParameter('js_static_analysis_domains_to_ignore'),
            json_decode($jsStaticAnalysisTask->getParameters())->{'domains-to-ignore'}
        );

        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueService');
        $this->assertFalse($resqueQueueService->isEmpty(
            'tasks-notify'
        ));
    }

    public function testCancelParentOfCrawlJob()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $jobFailedNoSitemapState = $stateService->get(JobService::FAILED_NO_SITEMAP_STATE);
        $jobCancelledState = $stateService->get(JobService::CANCELLED_STATE);

        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $parentJob = $this->jobFactory->create([
            JobFactory::KEY_TEST_TYPES => ['CSS validation', 'JS static analysis'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'CSS validation' => ['ignore-common-cdns' => 1],
                'JS static analysis' => ['ignore-common-cdns' => 1]
            ],
            JobFactory::KEY_USER => $user,
        ]);

        $parentJob->setState($jobFailedNoSitemapState);

        $entityManager->persist($parentJob);
        $entityManager->flush();

        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');
        $crawlJobContainer = $crawlJobContainerService->getForJob($parentJob);
        $crawlJobContainerService->prepare($crawlJobContainer);
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $response = $this->jobController->cancelAction(
            $parentJob->getWebsite()->getCanonicalUrl(),
            $parentJob->getId()
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals($jobCancelledState, $crawlJob->getState());
        $this->assertEquals($jobCancelledState, $parentJob->getState());
    }
}
