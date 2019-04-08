<?php

namespace App\Tests\Functional\Services\Job\Configuration;

use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Exception\Services\Job\Configuration\Exception as JobConfigurationServiceException;
use App\Services\UserService;

class ConfigurationServiceUpdateTest extends AbstractConfigurationServiceTest
{
    /**
     * @dataProvider updateExceptionDataProvider
     *
     * @param array $updatedJobConfigurationValues
     * @param string $expectedExceptionMessage
     * @param int $expectedExceptionCode
     */
    public function testUpdateException(
        $updatedJobConfigurationValues,
        $expectedExceptionMessage,
        $expectedExceptionCode
    ) {
        $userService = self::$container->get(UserService::class);

        $user = $userService->getPublicUser();
        $this->setUser($user);

        $existingJobConfigurationValues = [
            'label' => 'foo',
            'website' => 'http://example.com/',
            'type' => JobTypeService::FULL_SITE_NAME,
            'task-configuration' => [
                [
                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                ]
            ],
        ];

        $this->createJobConfigurationCollection([$existingJobConfigurationValues]);

        $jobConfigurationValues = [
            'label' => 'bar',
            'website' => 'http://example.com/',
            'type' => JobTypeService::SINGLE_URL_NAME,
            'task-configuration' => [
                [
                    'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                ]
            ],
        ];

        $jobConfigurationValuesModel = $this->createJobConfigurationValuesModel($jobConfigurationValues);

        $jobConfiguration = $this->jobConfigurationService->create($jobConfigurationValuesModel);

        $updateJobConfigurationValuesModel = $this->createJobConfigurationValuesModel(array_merge(
            $jobConfigurationValues,
            $updatedJobConfigurationValues
        ));

        $this->expectException(JobConfigurationServiceException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->expectExceptionCode($expectedExceptionCode);

        $this->jobConfigurationService->update($jobConfiguration, $updateJobConfigurationValuesModel);
    }

    /**
     * @return array
     */
    public function updateExceptionDataProvider()
    {
        return [
            'non-unique label' => [
                'updatedJobConfigurationValues' => [
                    'label' => 'foo',
                ],
                'expectedExceptionMessage' => 'Label "foo" is not unique',
                'expectedExceptionCode' => JobConfigurationServiceException::CODE_LABEL_NOT_UNIQUE
            ],
            'matching configuration already exists' => [
                'updatedJobConfigurationValues' => [
                    'type' => JobTypeService::FULL_SITE_NAME,
                ],
                'expectedExceptionMessage' => 'Matching configuration already exists',
                'expectedExceptionCode' => JobConfigurationServiceException::CODE_CONFIGURATION_ALREADY_EXISTS
            ],
        ];
    }

    /**
     * @dataProvider updateSuccessDataProvider
     *
     * @param array $jobConfigurationValues
     * @param array $updatedJobConfigurationValues
     * @param array $expectedSerializedUpdatedJobConfiguration
     */
    public function testUpdateSuccess(
        $jobConfigurationValues,
        $updatedJobConfigurationValues,
        $expectedSerializedUpdatedJobConfiguration
    ) {
        $userService = self::$container->get(UserService::class);

        $user = $userService->getPublicUser();
        $this->setUser($user);

        $jobConfiguration = $this->jobConfigurationService->create(
            $this->createJobConfigurationValuesModel($jobConfigurationValues)
        );

        $this->jobConfigurationService->update(
            $jobConfiguration,
            $this->createJobConfigurationValuesModel($updatedJobConfigurationValues)
        );

        $this->assertEquals($expectedSerializedUpdatedJobConfiguration, $jobConfiguration->jsonSerialize());
    }

    /**
     * @return array
     */
    public function updateSuccessDataProvider()
    {
        return [
            'no change' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'parameters string',
                ],
            ],
            'label change' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'label' => 'bar',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'bar',
                    'website' => 'http://example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'parameters string',
                ],
            ],
            'update parameters' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'updated parameters string',
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'updated parameters string',
                ],
            ],
            'update type' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'parameters string',
                ],
            ],
            'update task configuration' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'parameters string',
                ],
            ],
            'update website' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://foo.example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'foo',
                    'website' => 'http://foo.example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'parameters string',
                ],
            ],
            'update all values except label' => [
                'jobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://example.com/',
                    'type' => JobTypeService::FULL_SITE_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'parameters string',
                ],
                'updatedJobConfigurationValues' => [
                    'label' => 'foo',
                    'website' => 'http://foo.example.com/',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task-configuration' => [
                        [
                            'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                        ]
                    ],
                    'parameters' => 'updated parameters string',
                ],
                'expectedSerializedUpdatedJobConfiguration' => [
                    'label' => 'foo',
                    'website' => 'http://foo.example.com/',
                    'user' => 'public@simplytestable.com',
                    'type' => JobTypeService::SINGLE_URL_NAME,
                    'task_configurations' => [
                        [
                            'type' => TaskTypeService::CSS_VALIDATION_TYPE,
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                    'parameters' => 'updated parameters string',
                ],
            ],
        ];
    }
}
