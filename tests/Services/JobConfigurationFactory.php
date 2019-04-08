<?php

namespace App\Tests\Services;

use App\Entity\Job\Configuration;
use App\Services\JobTypeService;
use App\Services\UserService;
use App\Services\WebSiteService;
use Doctrine\ORM\EntityManagerInterface;

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

    private $defaultJobConfigurationValues = [
        self::KEY_LABEL => self::DEFAULT_LABEL,
        self::KEY_WEBSITE_URL => self::DEFAULT_WEBSITE_URL,
        self::KEY_TYPE => self::DEFAULT_TYPE,
    ];

    private $websiteService;
    private $jobTypeService;
    private $entityManager;
    private $jobTaskConfigurationFactory;

    public function __construct(
        UserService $userService,
        WebSiteService $webSiteService,
        JobTypeService $jobTypeService,
        EntityManagerInterface $entityManager,
        JobTaskConfigurationFactory $jobTaskConfigurationFactory
    ) {
        $this->defaultJobConfigurationValues[self::KEY_USER] = $userService->getPublicUser();
        $this->websiteService = $webSiteService;
        $this->jobTypeService = $jobTypeService;
        $this->entityManager = $entityManager;
        $this->jobTaskConfigurationFactory = $jobTaskConfigurationFactory;
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

        $website = $this->websiteService->get($jobConfigurationValues[self::KEY_WEBSITE_URL]);
        $jobType = $this->jobTypeService->get($jobConfigurationValues[self::KEY_TYPE]);

        $jobConfiguration = new Configuration();
        $jobConfiguration->setLabel($jobConfigurationValues[self::KEY_LABEL]);
        $jobConfiguration->setUser($jobConfigurationValues[self::KEY_USER]);
        $jobConfiguration->setWebsite($website);
        $jobConfiguration->setType($jobType);

        $parameters = $jobConfigurationValues[self::KEY_PARAMETERS] ?? [];
        $jobConfiguration->setParameters(json_encode($parameters));

        $this->entityManager->persist($jobConfiguration);
        $this->entityManager->flush();

        if (isset($jobConfigurationValues[self::KEY_TASK_CONFIGURATIONS])) {
            $taskConfigurationValuesCollection = $jobConfigurationValues[self::KEY_TASK_CONFIGURATIONS];

            foreach ($taskConfigurationValuesCollection as $taskConfigurationValues) {
                $taskConfiguration = $this->jobTaskConfigurationFactory->create($taskConfigurationValues);
                $taskConfiguration->setJobConfiguration($jobConfiguration);

                $this->entityManager->persist($taskConfiguration);
                $this->entityManager->flush();

                $jobConfiguration->addTaskConfiguration($taskConfiguration);
            }
        }

        $this->entityManager->persist($jobConfiguration);
        $this->entityManager->flush();

        return $jobConfiguration;
    }
}
