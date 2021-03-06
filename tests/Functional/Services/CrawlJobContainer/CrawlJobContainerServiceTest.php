<?php

namespace App\Tests\Functional\Services\CrawlJobContainer;

use App\Entity\CrawlJobContainer;
use App\Entity\Job\Job;
use App\Entity\User;
use App\Services\JobTypeService;
use App\Services\StateService;
use App\Services\WebSiteService;
use App\Tests\Services\UserFactory;
use App\Tests\Services\JobFactory;
use Doctrine\ORM\EntityManagerInterface;

class CrawlJobContainerServiceTest extends AbstractCrawlJobContainerServiceTest
{
    public function testGetForJobHasForJob()
    {
        $jobHasNoCrawlJob = $this->jobFactory->create([
            JobFactory::KEY_URL => 'http://foo.example.com/',
        ]);
        $this->assertFalse($this->crawlJobContainerService->hasForJob($jobHasNoCrawlJob));

        $user =  $this->userFactory->create();

        $jobHasCrawlJob = $this->jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_URL => 'http://bar.example.com/',
            JobFactory::KEY_USER => $user,
        ]);

        $crawlJobContainer = $this->crawlJobContainerService->getForJob($jobHasCrawlJob);

        $parentJob = $crawlJobContainer->getParentJob();
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $this->assertNotNull($crawlJob->getIdentifier());
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
        $stateService = self::$container->get(StateService::class);
        $websiteService = self::$container->get(WebSiteService::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $jobTypeService = self::$container->get(JobTypeService::class);

        $jobStateNames = [
            Job::STATE_STARTING,
            Job::STATE_CANCELLED,
            Job::STATE_COMPLETED,
            Job::STATE_IN_PROGRESS,
            Job::STATE_PREPARING,
            Job::STATE_QUEUED,
            Job::STATE_FAILED_NO_SITEMAP,
            Job::STATE_REJECTED,
            Job::STATE_RESOLVING,
            Job::STATE_RESOLVED,
        ];

        $incompleteStateNames = [
            Job::STATE_STARTING,
            Job::STATE_RESOLVING,
            Job::STATE_RESOLVED,
            Job::STATE_IN_PROGRESS,
            Job::STATE_PREPARING,
            Job::STATE_QUEUED
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

        foreach ($users as $userIndex => $user) {
            foreach ($jobStateNames as $jobStateName) {
                $url = 'http://' . $jobStateName . '.example.com/';
                $website = $websiteService->get($url);

                $crawlJob = Job::create(
                    $user,
                    $website,
                    $jobTypeService->getCrawlType(),
                    $stateService->get($jobStateName),
                    ''
                );

                $parentJob = Job::create(
                    $user,
                    $website,
                    $jobTypeService->getFullSiteType(),
                    $stateService->get(Job::STATE_FAILED_NO_SITEMAP),
                    ''
                );

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
