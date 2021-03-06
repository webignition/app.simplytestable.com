<?php

namespace App\Tests\Functional\Services\Request\Factory\Job;

use App\Request\Job\StartRequest;
use Doctrine\Common\Collections\ArrayCollection;
use App\Services\JobTypeService;
use App\Services\Request\Factory\Job\StartRequestFactory;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\HttpFoundation\Request;

class StartRequestFactoryTest extends AbstractBaseTestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $userEmail
     * @param array $requestRequest
     * @param string $expectedSiteRootUrl
     * @param string $expectedTaskConfigurationCollection
     * @param string $expectedJobParameters
     */
    public function testCreate(
        $userEmail,
        $requestRequest,
        $expectedSiteRootUrl,
        $expectedTaskConfigurationCollection,
        $expectedJobParameters
    ) {
        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);

        $request = new Request([], $requestRequest);

        self::$container->get('request_stack')->push($request);

        $this->setUser($user);

        $jobStartRequestFactory = self::$container->get(StartRequestFactory::class);
        /* @var StartRequest $jobStartRequest */
        $jobStartRequest = $jobStartRequestFactory->create($request);

        $this->assertEquals($jobStartRequest->getUser(), $user);
        $this->assertEquals($expectedSiteRootUrl, $jobStartRequest->getWebsite()->getCanonicalUrl());

        $taskConfigurationCollection = $jobStartRequest->getTaskConfigurationCollection();

        $this->assertCount(count($expectedTaskConfigurationCollection), $taskConfigurationCollection);

        foreach ($taskConfigurationCollection as $taskConfigurationIndex => $taskConfiguration) {
            $expectedTaskConfiguration = $expectedTaskConfigurationCollection[$taskConfigurationIndex];

            $this->assertEquals($expectedTaskConfiguration['type']['name'], $taskConfiguration->getType()->getName());
            $this->assertEquals($expectedTaskConfiguration['isEnabled'], $taskConfiguration->getIsEnabled());

            $taskConfigurationOptions = $taskConfiguration->getOptions();

            if ($taskConfigurationOptions instanceof ArrayCollection) {
                $taskConfigurationOptions = $taskConfigurationOptions->toArray();
            }

            if (is_array($taskConfigurationOptions)) {
                $this->assertEquals($expectedTaskConfiguration['options'], $taskConfigurationOptions);
            }
        }

        $this->assertEquals($expectedJobParameters, $jobStartRequest->getJobParameters());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        $expectedHtmlValidationOnlyTaskConfigurationCollection = [
            [
                'type' => [
                    'name' => 'HTML validation',
                ],
                'isEnabled' => true,
                'options' => [],
            ],
            [
                'type' => [
                    'name' => 'CSS validation',
                ],
                'isEnabled' => false,
                'options' => [],
            ],
            [
                'type' => [
                    'name' => 'Link integrity',
                ],
                'isEnabled' => false,
                'options' => [],
            ],
        ];

        return [
            'missing test types and test type options' => [
                'userEmail' => 'public@simplytestable.com',
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_URL => 'http://example.com/',
                ],
                'expectedSiteRootUrl' => 'http://example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'isEnabled' => true,
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'CSS validation',
                        ],
                        'isEnabled' => true,
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'Link integrity',
                        ],
                        'isEnabled' => true,
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'test types not array' => [
                'userEmail' => 'public@simplytestable.com',
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_URL => 'http://example.com/',
                    StartRequestFactory::PARAMETER_TEST_TYPES => 'foo',
                ],
                'expectedSiteRootUrl' => 'http://example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'isEnabled' => true,
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'CSS validation',
                        ],
                        'isEnabled' => true,
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'Link integrity',
                        ],
                        'isEnabled' => true,
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'test types present, test type options missing' => [
                'userEmail' => 'public@simplytestable.com',
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_URL => 'http://example.com/',
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                    ],
                ],
                'expectedSiteRootUrl' => 'http://example.com/',
                'expectedTaskConfigurationCollection' => $expectedHtmlValidationOnlyTaskConfigurationCollection,
                'expectedJobParameters' => [],
            ],
            'test type options not an array' => [
                'userEmail' => 'public@simplytestable.com',
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_URL => 'http://example.com/',
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                        'foo',
                    ],
                    StartRequestFactory::PARAMETER_TEST_TYPE_OPTIONS => 'foo',
                ],
                'expectedSiteRootUrl' => 'http://example.com/',
                'expectedTaskConfigurationCollection' => $expectedHtmlValidationOnlyTaskConfigurationCollection,
                'expectedJobParameters' => [],
            ],
            'test type options present, all invalid' => [
                'userEmail' => 'user@example.com',
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_URL => 'http://example.com/',
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                        'link integrity',
                    ],
                    StartRequestFactory::PARAMETER_TEST_TYPE_OPTIONS => [
                        'foo' => [
                            'foo' => 'foo',
                        ],
                    ],
                ],
                'expectedSiteRootUrl' => 'http://example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'isEnabled' => true,
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'CSS validation',
                        ],
                        'isEnabled' => false,
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'Link integrity',
                        ],
                        'isEnabled' => true,
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'test type options present, valid and invalid values' => [
                'userEmail' => 'user@example.com',
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_URL => 'http://foo.example.com/',
                    StartRequestFactory::PARAMETER_JOB_TYPE => JobTypeService::SINGLE_URL_NAME,
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                        'link integrity',
                    ],
                    StartRequestFactory::PARAMETER_TEST_TYPE_OPTIONS => [
                        'HTML validation' => [
                            'html-validation-foo' => 'html-validation-bar',
                        ],
                        'CSS validation' => [
                            'css-validation-foo' => 'css-validation-bar',
                        ],
                        'Link integrity' => [
                            'link-integrity-foo' => 'link-integrity-bar',
                        ],
                        'foo' => [
                            'bar',
                        ],
                    ],
                ],
                'expectedSiteRootUrl' => 'http://foo.example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'isEnabled' => true,
                        'options' => [
                            'html-validation-foo' => 'html-validation-bar',
                        ],
                    ],
                    [
                        'type' => [
                            'name' => 'CSS validation',
                        ],
                        'isEnabled' => false,
                        'options' => [
                            'css-validation-foo' => 'css-validation-bar',
                        ],
                    ],
                    [
                        'type' => [
                            'name' => 'Link integrity',
                        ],
                        'isEnabled' => true,
                        'options' => [
                            'link-integrity-foo' => 'link-integrity-bar',
                        ],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'job parameters not an array' => [
                'userEmail' => 'user@example.com',
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_URL => 'http://foo.example.com/',
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                    ],
                    StartRequestFactory::PARAMETER_JOB_PARAMETERS => 'foo',
                ],
                'expectedSiteRootUrl' => 'http://foo.example.com/',
                'expectedTaskConfigurationCollection' => $expectedHtmlValidationOnlyTaskConfigurationCollection,
                'expectedJobParameters' => [],
            ],
            'job parameters present and empty' => [
                'userEmail' => 'user@example.com',
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_URL => 'http://foo.example.com/',
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                    ],
                    StartRequestFactory::PARAMETER_JOB_PARAMETERS => [],
                ],
                'expectedSiteRootUrl' => 'http://foo.example.com/',
                'expectedTaskConfigurationCollection' => $expectedHtmlValidationOnlyTaskConfigurationCollection,
                'expectedJobParameters' => [],
            ],
            'job parameters present' => [
                'userEmail' => 'user@example.com',
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_URL => 'http://foo.example.com/',
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                    ],
                    StartRequestFactory::PARAMETER_JOB_PARAMETERS => [
                    'job-parameter-foo' => 'job-parameter-bar',
                    'a%20b' => 'foobar',
                    ],
                ],
                'expectedSiteRootUrl' => 'http://foo.example.com/',
                'expectedTaskConfigurationCollection' => $expectedHtmlValidationOnlyTaskConfigurationCollection,
                'expectedJobParameters' => [
                    'job-parameter-foo' => 'job-parameter-bar',
                    'a b' => 'foobar',
                ],
            ],
        ];
    }
}
