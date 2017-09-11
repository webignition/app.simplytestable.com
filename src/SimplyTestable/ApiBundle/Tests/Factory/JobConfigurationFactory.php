<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Services\JobTypeService;
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

        $userService = $container->get('simplytestable.services.userservice');
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

        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $website = $websiteService->fetch($jobConfigurationValues[self::KEY_WEBSITE_URL]);

        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $jobType = $jobTypeService->getByName($jobConfigurationValues[self::KEY_TYPE]);

        $jobConfiguration = new Configuration();
        $jobConfiguration->setLabel($jobConfigurationValues[self::KEY_LABEL]);
        $jobConfiguration->setUser($jobConfigurationValues[self::KEY_USER]);
        $jobConfiguration->setWebsite($website);
        $jobConfiguration->setType($jobType);

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entityManager->persist($jobConfiguration);
        $entityManager->flush($jobConfiguration);

        return $jobConfiguration;
    }
}
