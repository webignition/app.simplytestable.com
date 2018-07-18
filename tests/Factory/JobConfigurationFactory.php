<?php

namespace App\Tests\Factory;

use App\Entity\Job\Configuration;
use App\Services\JobTypeService;
use App\Services\UserService;
use App\Services\WebSiteService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JobConfigurationFactory
{
    const DEFAULT_LABEL = 'label';
    const DEFAULT_WEBSITE_URL = 'http://example.com';
    const DEFAULT_TYPE = JobTypeService::FULL_SITE_NAME;

    const KEY_LABEL = 'label';
    const KEY_USER = 'user';
    const KEY_WEBSITE_URL = 'website-url';
    const KEY_TYPE = 'type';
    const KEY_PARAMETERS = 'parameters';
    const KEY_TASK_CONFIGURATIONS = 'task-configurations';

    /**
     * @var array
     */
    private $defaultJobConfigurationValues = [
        self::KEY_LABEL => self::DEFAULT_LABEL,
        self::KEY_WEBSITE_URL => self::DEFAULT_WEBSITE_URL,
        self::KEY_TYPE => self::DEFAULT_TYPE,

    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $userService = $container->get(UserService::class);
        $this->defaultJobConfigurationValues[self::KEY_USER] = $userService->getPublicUser();
    }

    /**
     * @param array $jobConfigurationValues
     *
     * @return Configuration
     */
    public function create($jobConfigurationValues = [])
    {
        foreach ($this->defaultJobConfigurationValues as $key => $value) {
            if (!isset($jobConfigurationValues[$key])) {
                $jobConfigurationValues[$key] = $value;
            }
        }

        $websiteService = $this->container->get(WebSiteService::class);
        $jobTypeService = $this->container->get(JobTypeService::class);

        $website = $websiteService->get($jobConfigurationValues[self::KEY_WEBSITE_URL]);
        $jobType = $jobTypeService->get($jobConfigurationValues[self::KEY_TYPE]);

        $jobConfiguration = new Configuration();
        $jobConfiguration->setLabel($jobConfigurationValues[self::KEY_LABEL]);
        $jobConfiguration->setUser($jobConfigurationValues[self::KEY_USER]);
        $jobConfiguration->setWebsite($website);
        $jobConfiguration->setType($jobType);

        if (isset($jobConfigurationValues[self::KEY_PARAMETERS])) {
            $jobConfiguration->setParameters(json_encode($jobConfigurationValues[self::KEY_PARAMETERS]));
        }

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entityManager->persist($jobConfiguration);
        $entityManager->flush($jobConfiguration);

        if (isset($jobConfigurationValues[self::KEY_TASK_CONFIGURATIONS])) {
            $jobTaskConfigurationFactory = new JobTaskConfigurationFactory($this->container);

            $taskConfigurationValuesCollection = $jobConfigurationValues[self::KEY_TASK_CONFIGURATIONS];

            foreach ($taskConfigurationValuesCollection as $taskConfigurationValues) {
                $taskConfiguration = $jobTaskConfigurationFactory->create($taskConfigurationValues);
                $taskConfiguration->setJobConfiguration($jobConfiguration);

                $entityManager->persist($taskConfiguration);
                $entityManager->flush($taskConfiguration);

                $jobConfiguration->addTaskConfiguration($taskConfiguration);
            }
        }

        $entityManager->persist($jobConfiguration);
        $entityManager->flush($jobConfiguration);

        return $jobConfiguration;
    }
}
