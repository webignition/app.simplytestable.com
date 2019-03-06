<?php

namespace App\Tests\Services;

use App\Entity\Job\Job;
use App\Entity\User;
use App\Services\CrawlJobContainerService;
use App\Services\StateService;
use Symfony\Component\Yaml\Yaml;

class JobLoader
{
    private $userFactory;
    private $jobFactory;
    private $stateService;
    private $crawlJobContainerService;

    public function __construct(
        JobFactory $jobFactory,
        StateService $stateService,
        CrawlJobContainerService $crawlJobContainerService,
        UserFactory $userFactory
    ) {
        $this->userFactory = $userFactory;
        $this->jobFactory = $jobFactory;
        $this->stateService = $stateService;
        $this->crawlJobContainerService = $crawlJobContainerService;
    }

    /**
     * @param $fixture
     *
     * @param User[] $users
     * @return Job[]
     */
    public function load($fixture, $users)
    {
        $fixturePath = __DIR__ . '/../Fixtures/' . $fixture;
        $fixtureRealPath = realpath($fixturePath);

        if (false === $fixtureRealPath) {
            throw new \InvalidArgumentException(sprintf(
                'Fixture %s not found, expected at %s',
                $fixture,
                $fixturePath
            ));
        }

        $fixtureData = Yaml::parse(file_get_contents($fixtureRealPath));

        $jobs = [];

        foreach ($fixtureData as $jobValues) {
            if (isset($jobValues['user'])) {
                $jobValues['user'] = $users[$jobValues['user']];
            }

            $parentJob = null;

            if ($jobValues['type'] == 'crawl') {
                $job = $this->jobFactory->createResolveAndPrepareStandardCrawlJob($jobValues);

                $crawlJobContainer = $this->crawlJobContainerService->getForJob($job);

                $jobs[] = $crawlJobContainer->getParentJob();
            } else {
                $job = $this->jobFactory->create($jobValues);
            }

            if (isset($jobValues['state'])) {
                $state = $this->stateService->get($jobValues['state']);
                $job->setState($state);
                $this->jobFactory->save($job);
            }

            $jobs[] = $job;
        }

        return $jobs;
    }
}
