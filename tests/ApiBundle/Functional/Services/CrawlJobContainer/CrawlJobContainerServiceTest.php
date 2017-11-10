<?php

namespace Tests\ApiBundle\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;

class CrawlJobContainerServiceTest extends AbstractCrawlJobContainerServiceTest
{
    public function testGetForJobHasForJob()
    {
        $jobHasNoCrawlJob = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://foo.example.com/',
        ]);
        $this->assertFalse($this->crawlJobContainerService->hasForJob($jobHasNoCrawlJob));

        $user =  $this->userFactory->create();

        $jobHasCrawlJob = $this->jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_SITE_ROOT_URL => 'http://bar.example.com/',
            JobFactory::KEY_USER => $user,
        ]);

        $this->assertTrue($this->crawlJobContainerService->hasForJob($jobHasCrawlJob));

        $crawlJobContainer = $this->crawlJobContainerService->getForJob($jobHasCrawlJob);

        $parentJob = $crawlJobContainer->getParentJob();
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $this->assertEquals($jobHasCrawlJob, $parentJob);

        $this->assertTrue($this->crawlJobContainerService->hasForJob($parentJob));
        $this->assertTrue($this->crawlJobContainerService->hasForJob($crawlJob));

        $parentJobCrawlJobContainer = $this->crawlJobContainerService->getForJob($parentJob);
        $crawlJobCrawlJobContainer = $this->crawlJobContainerService->getForJob($crawlJob);

        $this->assertEquals($crawlJobContainer, $parentJobCrawlJobContainer);
        $this->assertEquals($crawlJobContainer, $crawlJobCrawlJobContainer);
    }

    public function testGetAllActiveForUser()
    {
        $stateService = $this->container->get(StateService::class);
        $websiteService = $this->container->get(WebSiteService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $jobStateNames = [
            JobService::STARTING_STATE,
            JobService::CANCELLED_STATE,
            JobService::COMPLETED_STATE,
            JobService::IN_PROGRESS_STATE,
            JobService::PREPARING_STATE,
            JobService::QUEUED_STATE,
            JobService::FAILED_NO_SITEMAP_STATE,
            JobService::REJECTED_STATE,
            JobService::RESOLVING_STATE,
            JobService::RESOLVED_STATE,
        ];

        $incompleteStateNames = [
            JobService::STARTING_STATE,
            JobService::RESOLVING_STATE,
            JobService::RESOLVED_STATE,
            JobService::IN_PROGRESS_STATE,
            JobService::PREPARING_STATE,
            JobService::QUEUED_STATE
        ];

        /* @var User[] $users */
        $users = [
            $this->userFactory->create([
                UserFactory::KEY_EMAIL => 'user0@example.com',
            ]),
            $this->userFactory->create([
                UserFactory::KEY_EMAIL => 'user1@example.com',
            ]),
        ];

        $expectedCrawlJobContainerIds = [
            $users[0]->getEmail() => [],
            $users[1]->getEmail() => [],
        ];

        $jobFailedNoSitemapState = $stateService->get(JobService::FAILED_NO_SITEMAP_STATE);

        foreach ($users as $userIndex => $user) {
            foreach ($jobStateNames as $jobStateName) {
                $url = 'http://' . $jobStateName . '.example.com/';
                $website = $websiteService->get($url);

                $crawlJob = new Job();
                $crawlJob->setState($stateService->get($jobStateName));
                $crawlJob->setUser($user);
                $crawlJob->setWebsite($website);

                $parentJob = new Job();
                $parentJob->setUser($user);
                $parentJob->setWebsite($website);
                $parentJob->setState($jobFailedNoSitemapState);

                $crawlJobContainer = new CrawlJobContainer();
                $crawlJobContainer->setCrawlJob($crawlJob);
                $crawlJobContainer->setParentJob($parentJob);

                $entityManager->persist($crawlJob);
                $entityManager->flush();

                $entityManager->persist($parentJob);
                $entityManager->flush();

                $entityManager->persist($crawlJobContainer);
                $entityManager->flush();

                if (in_array($jobStateName, $incompleteStateNames)) {
                    $expectedCrawlJobContainerIds[$user->getEmail()][] = $crawlJobContainer->getId();
                }
            }
        }

        $user0CrawlJobContainers = $this->crawlJobContainerService->getAllActiveForUser($users[0]);
        $user1CrawlJobContainers = $this->crawlJobContainerService->getAllActiveForUser($users[1]);

        $this->assertNotEquals($user0CrawlJobContainers, $user1CrawlJobContainers);

        $this->assertEquals(
            $expectedCrawlJobContainerIds[$users[0]->getEmail()],
            $this->getCrawlJobContainerIds($user0CrawlJobContainers)
        );

        $this->assertEquals(
            $expectedCrawlJobContainerIds[$users[1]->getEmail()],
            $this->getCrawlJobContainerIds($user1CrawlJobContainers)
        );
    }

    /**
     * @param CrawlJobContainer[] $crawlJobContainers
     * @return int[]
     */
    private function getCrawlJobContainerIds($crawlJobContainers)
    {
        $crawlJobContainerIds = [];

        foreach ($crawlJobContainers as $crawlJobContainer) {
            $crawlJobContainerIds[] = $crawlJobContainer->getId();
        }

        return $crawlJobContainerIds;
    }
}
