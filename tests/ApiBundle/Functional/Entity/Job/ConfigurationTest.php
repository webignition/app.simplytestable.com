<?php

namespace Tests\ApiBundle\Functional\Entity\Job;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use SimplyTestable\ApiBundle\Entity\Job\TaskConfiguration;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\JobTaskConfigurationFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class ConfigurationTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory(self::$container);
        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');
    }

    /**
     * @dataProvider persistDataProvider
     *
     * @param string $jobTypeName
     * @param string $label
     * @param string $websiteUrl
     * @param string $parameters
     */
    public function testPersist(
        $jobTypeName,
        $label,
        $websiteUrl,
        $parameters,
        $taskConfigurationValuesCollection
    ) {
        $jobTypeService = self::$container->get(JobTypeService::class);
        $userService = self::$container->get(UserService::class);
        $websiteService = self::$container->get(WebSiteService::class);
        $taskConfigurationFactory = new JobTaskConfigurationFactory(self::$container);

        $configurationRepository = $this->entityManager->getRepository(Configuration::class);

        $jobType = $jobTypeService->get($jobTypeName);
        $user = $userService->getPublicUser();
        $website = $websiteService->get($websiteUrl);

        $configuration = new Configuration();

        $configuration->setType($jobType);
        $configuration->setUser($user);
        $configuration->setLabel($label);
        $configuration->setWebsite($website);
        $configuration->setParameters($parameters);

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        foreach ($taskConfigurationValuesCollection as $taskConfigurationValues) {
            $taskConfiguration = $taskConfigurationFactory->create($taskConfigurationValues);
            $taskConfiguration->setJobConfiguration($configuration);
            $this->entityManager->persist($taskConfiguration);
            $this->entityManager->flush();

            $configuration->addTaskConfiguration($taskConfiguration);
        }

        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        $configurationId = $configuration->getId();
        $this->assertNotNull($configurationId);

        $this->entityManager->clear();

        /* @var Configuration $retrievedConfiguration */
        $retrievedConfiguration = $configurationRepository->find($configurationId);

        $this->assertInstanceOf(Configuration::class, $retrievedConfiguration);
        $this->assertEquals($configurationId, $retrievedConfiguration->getId());

        $this->assertEquals($jobTypeName, $retrievedConfiguration->getType()->getName());
        $this->assertEquals($user->getId(), $retrievedConfiguration->getUser()->getId());
        $this->assertEquals($label, $retrievedConfiguration->getLabel());
        $this->assertEquals($websiteUrl, $retrievedConfiguration->getWebsite()->getCanonicalUrl());
        $this->assertEquals($parameters, $retrievedConfiguration->getParameters());

        /* @var PersistentCollection $retrievedTaskConfigurations */
        $retrievedTaskConfigurations = $configuration->getTaskConfigurations();

        foreach ($taskConfigurationValuesCollection as $taskConfigurationIndex => $taskConfigurationValues) {
            /* @var TaskConfiguration $retrievedTaskConfiguration */
            $retrievedTaskConfiguration = $retrievedTaskConfigurations->get($taskConfigurationIndex);

            $this->assertEquals(
                $taskConfigurationValues[JobTaskConfigurationFactory::KEY_TYPE],
                $retrievedTaskConfiguration->getType()->getName()
            );

            $this->assertEquals(
                $taskConfigurationValues[JobTaskConfigurationFactory::KEY_OPTIONS],
                $retrievedTaskConfiguration->getOptions()
            );
        }
    }

    /**
     * @return array
     */
    public function persistDataProvider()
    {
        return [
            'no parameters, no task configurations' => [
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'label' => 'foo label',
                'websiteUrl' => 'http://example.com/',
                'parameters' => null,
                'taskConfigurationValuesCollection' => [],
            ],
            'has parameters, has task configurations' => [
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'label' => 'foo label',
                'websiteUrl' => 'http://example.com/',
                'parameters' => 'foo',
                'taskConfigurationValuesCollection' => [
                    [
                        JobTaskConfigurationFactory::KEY_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                        JobTaskConfigurationFactory::KEY_OPTIONS => [
                            'html-foo' => 'html-bar',
                        ],
                    ],
                    [
                        JobTaskConfigurationFactory::KEY_TYPE => TaskTypeService::CSS_VALIDATION_TYPE,
                        JobTaskConfigurationFactory::KEY_OPTIONS => [
                            'css-foo' => 'css-bar',
                        ],
                    ],
                    [
                        JobTaskConfigurationFactory::KEY_TYPE => TaskTypeService::LINK_INTEGRITY_TYPE,
                        JobTaskConfigurationFactory::KEY_OPTIONS => [],
                    ],
                ],
            ],
        ];
    }
}
