<?php

namespace App\Tests\Functional\Controller\Job\Job;

use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Services\CrawlJobContainerService;
use App\Services\JobPreparationService;
use App\Services\JobService;
use App\Tests\Services\JobFactory;
use Symfony\Component\HttpFoundation\Response;
use App\Tests\Factory\MockFactory;
use App\Services\Resque\QueueService as ResqueQueueService;
use App\Services\StateService;
use App\Services\TaskTypeDomainsToIgnoreService;
use App\Services\UserService;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @group Controller/Job/JobController
 */
class JobControllerCancelActionTest extends AbstractJobControllerTest
{
    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => self::$container->get('router')->generate('job_job_cancel', [
                'test_id' => $job->getId(),
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCancelActionSuccess()
    {
        $userService = self::$container->get(UserService::class);
        $user = $userService->getPublicUser();

        $this->setUser($user);

        $canonicalUrl = 'http://example.com/';

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_URL => $canonicalUrl,
            JobFactory::KEY_USER => $user,
            JobFactory::KEY_TASKS => [
                [
                    JobFactory::KEY_TASK_STATE => Task::STATE_IN_PROGRESS,
                ],
            ],
        ]);

        $response = $this->callCancelAction($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertTrue($response->isSuccessful());
    }

    public function testCancelCrawlJob()
    {
        $stateService = self::$container->get(StateService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $resqueQueueService = self::$container->get(ResqueQueueService::class);

        $resqueQueueService->getResque()->getQueue('tasks-notify')->clear();

        $jobFailedNoSitemapState = $stateService->get(Job::STATE_FAILED_NO_SITEMAP);
        $jobCancelledState = $stateService->get(Job::STATE_CANCELLED);
        $jobQueuedState = $stateService->get(Job::STATE_QUEUED);

        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $parentJob = $this->jobFactory->create([
            JobFactory::KEY_TEST_TYPES => ['CSS validation'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'CSS validation' => ['ignore-common-cdns' => 1],
            ],
            JobFactory::KEY_USER => $user,
        ]);

        $parentJob->setState($jobFailedNoSitemapState);

        $entityManager->persist($parentJob);
        $entityManager->flush();

        $crawlJobContainerService = self::$container->get(CrawlJobContainerService::class);
        $crawlJobContainer = $crawlJobContainerService->getForJob($parentJob);
        $crawlJobContainerService->prepare($crawlJobContainer);
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $cssValidationDomainsToIgnore = self::$container->getParameter('css_validation_domains_to_ignore');

        $taskTypeDomainsToIgnoreService = MockFactory::createTaskTypeDomainsToIgnoreService([
            'CSS validation' => $cssValidationDomainsToIgnore,
        ]);

        $response = $this->callCancelAction(
            $crawlJob->getWebsite()->getCanonicalUrl(),
            $crawlJob->getId(),
            $taskTypeDomainsToIgnoreService
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals((string)$jobCancelledState, (string)$crawlJob->getState());
        $this->assertEquals((string)$jobQueuedState, (string)$parentJob->getState());

        /* @var Task $cssValidationTask */
        $cssValidationTask = null;

        foreach ($parentJob->getTasks() as $currentTask) {
            if (empty($cssValidationTask) && $currentTask->getType()->getName() == 'CSS validation') {
                $cssValidationTask = $currentTask;
            }
        }

        $this->assertEquals(
            $cssValidationDomainsToIgnore,
            $cssValidationTask->getParameters()->get('domains-to-ignore')
        );

        $this->assertFalse($resqueQueueService->isEmpty(
            'tasks-notify'
        ));
    }

    public function testCancelParentOfCrawlJob()
    {
        $stateService = self::$container->get(StateService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $jobFailedNoSitemapState = $stateService->get(Job::STATE_FAILED_NO_SITEMAP);
        $jobCancelledState = $stateService->get(Job::STATE_CANCELLED);

        $user = $this->userFactory->createAndActivateUser();
        $this->setUser($user);

        $parentJob = $this->jobFactory->create([
            JobFactory::KEY_TEST_TYPES => ['CSS validation'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'CSS validation' => ['ignore-common-cdns' => 1],
            ],
            JobFactory::KEY_USER => $user,
        ]);

        $parentJob->setState($jobFailedNoSitemapState);

        $entityManager->persist($parentJob);
        $entityManager->flush();

        $crawlJobContainerService = self::$container->get(CrawlJobContainerService::class);
        $crawlJobContainer = $crawlJobContainerService->getForJob($parentJob);
        $crawlJobContainerService->prepare($crawlJobContainer);
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $taskTypeDomainsToIgnoreService = MockFactory::createTaskTypeDomainsToIgnoreService([
            'CSS validation' => self::$container->getParameter('css_validation_domains_to_ignore'),
        ]);

        $response = $this->callCancelAction(
            $parentJob->getWebsite()->getCanonicalUrl(),
            $parentJob->getId(),
            $taskTypeDomainsToIgnoreService
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals($jobCancelledState, $crawlJob->getState());
        $this->assertEquals($jobCancelledState, $parentJob->getState());
    }

    /**
     * @param string $siteRootUrl
     * @param int $testId
     * @param TaskTypeDomainsToIgnoreService|null $taskTypeDomainsToIgnoreService
     *
     * @return Response
     */
    private function callCancelAction($siteRootUrl, $testId, $taskTypeDomainsToIgnoreService = null)
    {
        if (empty($taskTypeDomainsToIgnoreService)) {
            $taskTypeDomainsToIgnoreService = MockFactory::createTaskTypeDomainsToIgnoreService();
        }

        return $this->jobController->cancelAction(
            MockFactory::createApplicationStateService(),
            self::$container->get(JobService::class),
            self::$container->get(CrawlJobContainerService::class),
            self::$container->get(JobPreparationService::class),
            self::$container->get(ResqueQueueService::class),
            self::$container->get(StateService::class),
            $taskTypeDomainsToIgnoreService,
            $siteRootUrl,
            $testId
        );
    }
}
