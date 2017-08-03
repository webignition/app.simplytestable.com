<?php

namespace SimplyTestable\ApiBundle\Tests\Fixtures\Loader;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class JobLoader
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->userFactory = new UserFactory($this->container);
    }

    /**
     * @param $fixture
     *
     * @param User[] $users
     * @return Job[]
     */
    public function load($fixture, $users)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');

        $fixturePath = __DIR__ . '/../' . $fixture;
        $fixtureRealPath = realpath(__DIR__ . '/../' . $fixture);

        if (false === $fixtureRealPath) {
            throw new \InvalidArgumentException(sprintf(
                'Fixture %s not found, expected at %s',
                $fixture,
                $fixturePath
            ));
        }

        $fixtureData = Yaml::parse(file_get_contents($fixtureRealPath));

        $jobFactory = new JobFactory($this->container);

        $jobs = [];

        foreach ($fixtureData as $jobValues) {
            if (isset($jobValues['user'])) {
                $jobValues['user'] = $users[$jobValues['user']];
            }

            $parentJob = null;

            if ($jobValues['type'] == 'crawl') {
                $job = $jobFactory->createResolveAndPrepareStandardCrawlJob($jobValues);

                $crawlJobContainer = $crawlJobContainerService->getForJob($job);

                $jobs[] = $crawlJobContainer->getParentJob();
            } else {
                $job = $jobFactory->create($jobValues);
            }

            if (isset($jobValues['state'])) {
                $state = $stateService->fetch($jobValues['state']);
                $job->setState($state);
                $jobFactory->save($job);
            }

            $jobs[] = $job;
        }

        return $jobs;
    }
}
