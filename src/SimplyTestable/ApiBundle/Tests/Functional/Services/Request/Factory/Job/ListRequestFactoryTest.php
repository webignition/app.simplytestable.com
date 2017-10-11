<?php

namespace SimplyTestable\ApiBundle\Tests\Request\Factory\Job;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\Request;

class ListRequestFactoryTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $userEmail
     * @param array $requestQuery
     * @param string $expectedUrlFilter
     * @param string[] $expectedStateNamesToExclude
     * @param string[] $expectedTypeNamesToExclude
     */
    public function testCreate(
        $userEmail,
        $requestQuery,
        $expectedUrlFilter,
        $expectedStateNamesToExclude,
        $expectedTypeNamesToExclude
    ) {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);

        $request = new Request($requestQuery);

        $this->container->get('request_stack')->push($request);

        $this->setUser($user);

        $jobListRequestFactory = $this->container->get('simplytestable.services.request.factory.job.list');
        $jobListRequest = $jobListRequestFactory->create();

        $this->assertEquals($expectedUrlFilter, $jobListRequest->getUrlFilter());

        $statesToExclude = $jobListRequest->getStatesToExclude();
        $this->assertCount(count($expectedStateNamesToExclude), $statesToExclude);

        foreach ($statesToExclude as $stateIndex => $state) {
            $expectedStateName = $expectedStateNamesToExclude[$stateIndex];
            $this->assertEquals($expectedStateName, $state->getName());
        }

        $typesToExclude = $jobListRequest->getTypesToExclude();
        $this->assertCount(count($expectedTypeNamesToExclude), $typesToExclude);

        foreach ($typesToExclude as $typeIndex => $type) {
            $expectedTypeName = $expectedTypeNamesToExclude[$typeIndex];
            $this->assertEquals($expectedTypeName, $type->getName());
        }
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'empty request' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [],
                'expectedUrlFilter' => null,
                'expectedStateNamesToExclude' => [],
                'expectedTypeNamesToExclude' => [
                    'crawl',
                ],
            ],
            'exclude current states' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [
                    'exclude-current' => true,
                ],
                'expectedUrlFilter' => null,
                'expectedStateNamesToExclude' => [
                    'job-in-progress',
                    'job-queued',
                    'job-preparing',
                    'job-new',
                    'job-resolving',
                    'job-resolved',
                ],
                'expectedTypeNamesToExclude' => [
                    'crawl',
                ],
            ],
            'exclude finished states' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [
                    'exclude-finished' => true,
                ],
                'expectedUrlFilter' => null,
                'expectedStateNamesToExclude' => [
                    'job-cancelled',
                    'job-completed',
                    'job-failed-no-sitemap',
                    'job-rejected',
                ],
                'expectedTypeNamesToExclude' => [
                    'crawl',
                ],
            ],
            'exclude states' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [
                    'exclude-states' => [
                        'completed',
                        'cancelled',
                    ]
                ],
                'expectedUrlFilter' => null,
                'expectedStateNamesToExclude' => [
                    'job-cancelled',
                    'job-completed',
                ],
                'expectedTypeNamesToExclude' => [
                    'crawl',
                ],
            ],
        ];
    }

    /**
     * @dataProvider createWithCrawlJobsDataProvider
     *
     * @param string $userEmail
     * @param array $requestQuery
     * @param int[] $expectedJobIndicesToExclude
     * @param int[] $expectedJobIndicesToInclude
     */
    public function testCreateWithCrawlJobs(
        $userEmail,
        $requestQuery,
        $expectedJobIndicesToExclude,
        $expectedJobIndicesToInclude
    ) {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);

        $jobFactory = new JobFactory($this->container);
        $jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
            JobFactory::KEY_SITE_ROOT_URL => 'http://foo.example.com',
        ]);

        $jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
            JobFactory::KEY_SITE_ROOT_URL => 'http://bar.example.com',
        ]);

        $request = new Request($requestQuery);

        $this->container->get('request_stack')->push($request);

        $this->setUser($user);

        $jobListRequestFactory = $this->container->get('simplytestable.services.request.factory.job.list');
        $jobListRequest = $jobListRequestFactory->create();

        $crawlJobContainerRepository = $entityManager->getRepository(CrawlJobContainer::class);

        /* @var CrawlJobContainer[] $crawlJobContainers */
        $crawlJobContainers = $crawlJobContainerRepository->findAll();

        $crawlJobParentIds = [];
        foreach ($crawlJobContainers as $crawlJobContainer) {
            $crawlJobParentIds[] = $crawlJobContainer->getParentJob()->getId();
        }

        $expectedJobIdsToExclude = [];
        $expectedJobIdsToInclude = [];

        foreach ($crawlJobParentIds as $crawlJobIndex => $crawlJobParentId) {
            if (in_array($crawlJobIndex, $expectedJobIndicesToExclude)) {
                $expectedJobIdsToExclude[] = $crawlJobParentId;
            }

            if (in_array($crawlJobIndex, $expectedJobIndicesToInclude)) {
                $expectedJobIdsToInclude[] = $crawlJobParentId;
            }
        }

        $this->assertEquals($expectedJobIdsToExclude, $jobListRequest->getJobIdsToExclude());
        $this->assertEquals($expectedJobIdsToInclude, $jobListRequest->getJobIdsToInclude());
    }

    /**
     * @return array
     */
    public function createWithCrawlJobsDataProvider()
    {
        return [
            'include current' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [],
                'expectedJobIndicesToExclude' => [],
                'expectedJobIndicesToInclude' => [0, 1],
            ],
            'exclude current' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [
                    'exclude-current' => true,
                ],
                'expectedJobIndicesToExclude' => [0, 1],
                'expectedJobIndicesToInclude' => [],
            ],
        ];
    }
}
