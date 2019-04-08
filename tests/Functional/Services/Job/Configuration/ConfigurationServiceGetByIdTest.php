<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\Job\Configuration;

use App\Entity\User;
use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Tests\Services\UserFactory;

class ConfigurationServiceGetByIdTest extends AbstractConfigurationServiceTest
{
    /**
     * @var User[]
     */
    private $users;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $userFactory = self::$container->get(UserFactory::class);
        $this->users = $userFactory->createPublicPrivateAndTeamUserSet();
    }

    /**
     * @dataProvider getDataProvider
     */
    public function testGet(
        string $userName,
        array $existingJobConfigurationValuesCollection,
        int $index,
        ?int $expectedJobConfigurationIndex
    ) {
        $jobConfigurationCollection = $this->createJobConfigurationCollection(
            $existingJobConfigurationValuesCollection,
            $this->users
        );

        $jobConfiguration = $jobConfigurationCollection[$index] ?? null;
        $jobConfigurationId = $jobConfiguration ? $jobConfiguration->getId() : 0;

        $user = $this->users[$userName];
        $this->setUser($user);

        $jobConfiguration = $this->jobConfigurationService->getById($jobConfigurationId);

        if (is_null($expectedJobConfigurationIndex)) {
            $this->assertNull($jobConfiguration);
        } else {
            $expectedJobConfigurationId = $jobConfigurationCollection[$expectedJobConfigurationIndex]->getId();

            $this->assertEquals($expectedJobConfigurationId, $jobConfiguration->getId());
        }
    }

    public function getDataProvider(): array
    {
        $teamJobConfigurationValuesCollection = [
            [
                'userName' => 'member1',
                'label' => 'member1',
                'website' => 'http://foo.example.com/',
                'type' => JobTypeService::FULL_SITE_NAME,
                'task-configuration' => [
                    [
                        'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ]
                ],
            ],
            [
                'userName' => 'member2',
                'label' => 'member2',
                'website' => 'http://member2.example.com/',
                'type' => JobTypeService::FULL_SITE_NAME,
                'task-configuration' => [
                    [
                        'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ]
                ],
            ],
            [
                'userName' => 'leader',
                'label' => 'leader',
                'website' => 'http://leader.example.com/',
                'type' => JobTypeService::FULL_SITE_NAME,
                'task-configuration' => [
                    [
                        'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                    ]
                ],
            ],

        ];

        return [
            'no existing job configurations' => [
                'userName' => 'public',
                'existingJobConfigurationValuesCollection' => [],
                'index' => 0,
                'expectedJobConfigurationIndex' => null,
            ],
            'match for user' => [
                'userName' => 'public',
                'existingJobConfigurationValuesCollection' => [
                    [
                        'userName' => 'public',
                        'label' => 'foo',
                        'website' => 'http://foo.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'task-configuration' => [
                            [
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            ]
                        ],
                    ],
                    [
                        'userName' => 'public',
                        'label' => 'bar',
                        'website' => 'http://bar.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'task-configuration' => [
                            [
                                'type' => TaskTypeService::HTML_VALIDATION_TYPE,
                            ]
                        ],
                    ],
                ],
                'index' => 1,
                'expectedJobConfigurationIndex' => 1,
            ],
            'match for leader' => [
                'userName' => 'leader',
                'existingJobConfigurationValuesCollection' => $teamJobConfigurationValuesCollection,
                'index' => 2,
                'expectedJobConfigurationIndex' => 2,
            ],
            'match for member1' => [
                'userName' => 'member1',
                'existingJobConfigurationValuesCollection' => $teamJobConfigurationValuesCollection,
                'index' => 0,
                'expectedJobConfigurationIndex' => 0,
            ],
            'match for member2' => [
                'userName' => 'member2',
                'existingJobConfigurationValuesCollection' => $teamJobConfigurationValuesCollection,
                'index' => 1,
                'expectedJobConfigurationIndex' => 1,
            ],
        ];
    }
}
