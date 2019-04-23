<?php

namespace App\Tests\Functional\Services\Request\Factory\Job;

use App\Entity\CrawlJobContainer;
use App\Repository\CrawlJobContainerRepository;
use App\Services\Request\Factory\Job\ListRequestFactory;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\JobFactory;
use Symfony\Component\HttpFoundation\Request;

class ListRequestFactoryTest extends AbstractBaseTestCase
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
        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);

        $request = new Request($requestQuery);

        self::$container->get('request_stack')->push($request);

        $this->setUser($user);

        $jobListRequestFactory = self::$container->get(ListRequestFactory::class);
        $jobListRequest = $jobListRequestFactory->create();

        $this->assertEquals($expectedUrlFilter, $jobListRequest->getUrlFilter());

        $statesToExclude = $jobListRequest->getStatesToExclude();
        $stateNamesToExclude = [];

        foreach ($statesToExclude as $state) {
            $stateNamesToExclude[] = (string) $state;
        }

        sort($stateNamesToExclude);

        $this->assertEquals($expectedStateNamesToExclude, $stateNamesToExclude);

        $typesToExclude = $jobListRequest->getTypesToExclude();
        $typeNamesToExclude = [];

        foreach ($typesToExclude as $type) {
            $typeNamesToExclude[] = $type->getName();
        }

        sort($typeNamesToExclude);

        $this->assertEquals($expectedTypeNamesToExclude, $typeNamesToExclude);

        $listRequestUser = $jobListRequest->getUser();
        $this->assertEquals($user, $listRequestUser);
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
                    'job-new',
                    'job-preparing',
                    'job-queued',
                    'job-resolved',
                    'job-resolving',
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
                    'job-expired',
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
        $crawlJobContainerRepository = self::$container->get(CrawlJobContainerRepository::class);

        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);

        $jobFactory = self::$container->get(JobFactory::class);
        $jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
            JobFactory::KEY_URL => 'http://foo.example.com',
        ]);

        $jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
            JobFactory::KEY_URL => 'http://bar.example.com',
        ]);

        $request = new Request($requestQuery);

        self::$container->get('request_stack')->push($request);

        $this->setUser($user);

        $jobListRequestFactory = self::$container->get(ListRequestFactory::class);
        $jobListRequest = $jobListRequestFactory->create();

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
