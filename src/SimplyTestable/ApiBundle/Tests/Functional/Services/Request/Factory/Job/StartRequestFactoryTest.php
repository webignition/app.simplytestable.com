<?php

namespace SimplyTestable\ApiBundle\Tests\Request\Factory\Job;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery\MockInterface;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Job\StartRequestFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class StartRequestFactoryTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param string $userEmail
     * @param array $requestQuery
     * @param array $requestRequest
     * @param array $requestAttributes
     * @param string $expectedSiteRootUrl
     * @param string $expectedTaskConfigurationCollection
     * @param string $expectedJobParameters
     */
    public function testCreate(
        $userEmail,
        $requestQuery,
        $requestRequest,
        $requestAttributes,
        $expectedSiteRootUrl,
        $expectedTaskConfigurationCollection,
        $expectedJobParameters
    ) {
        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);

        $request = new Request($requestQuery, $requestRequest, $requestAttributes);

        $this->container->get('request_stack')->push($request);

        $this->setUser($user);

        $jobStartRequestFactory = $this->container->get('simplytestable.services.request.factory.job.start');
        $jobStartRequest = $jobStartRequestFactory->create();

        $this->assertEquals($jobStartRequest->getUser(), $user);
        $this->assertEquals($expectedSiteRootUrl, $jobStartRequest->getWebsite()->getCanonicalUrl());

        $taskConfigurationCollection = $jobStartRequest->getTaskConfigurationCollection();

        $this->assertEquals(count($expectedTaskConfigurationCollection), $taskConfigurationCollection->count());

        foreach ($taskConfigurationCollection->get() as $taskConfigurationIndex => $taskConfiguration) {
            $expectedTaskConfiguration = $expectedTaskConfigurationCollection[$taskConfigurationIndex];

            $this->assertEquals($expectedTaskConfiguration['type']['name'], $taskConfiguration->getType()->getName());
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
        return [
            'missing test types and test type options' => [
                'userEmail' => 'public@simplytestable.com',
                'requestQuery' => [],
                'requestRequest' => [],
                'requestAttributes' => [
                    StartRequestFactory::PARAMETER_SITE_ROOT_URL => 'http://example.com/',
                ],
                'expectedSiteRootUrl' => 'http://example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'CSS validation',
                        ],
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'JS static analysis',
                        ],
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'Link integrity',
                        ],
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'test types not array' => [
                'userEmail' => 'public@simplytestable.com',
                'requestQuery' => [],
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_TEST_TYPES => 'foo',
                ],
                'requestAttributes' => [
                    StartRequestFactory::PARAMETER_SITE_ROOT_URL => 'http://example.com/',
                ],
                'expectedSiteRootUrl' => 'http://example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'CSS validation',
                        ],
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'JS static analysis',
                        ],
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'Link integrity',
                        ],
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'test types present, test type options missing' => [
                'userEmail' => 'public@simplytestable.com',
                'requestQuery' => [],
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                    ],
                ],
                'requestAttributes' => [
                    StartRequestFactory::PARAMETER_SITE_ROOT_URL => 'http://example.com/',
                ],
                'expectedSiteRootUrl' => 'http://example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'test type options not an array' => [
                'userEmail' => 'public@simplytestable.com',
                'requestQuery' => [],
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                        'foo',
                    ],
                    StartRequestFactory::PARAMETER_TEST_TYPE_OPTIONS => 'foo',
                ],
                'requestAttributes' => [
                    StartRequestFactory::PARAMETER_SITE_ROOT_URL => 'http://example.com/',
                ],
                'expectedSiteRootUrl' => 'http://example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'test type options present, all invalid' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [],
                'requestRequest' => [
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
                'requestAttributes' => [
                    StartRequestFactory::PARAMETER_SITE_ROOT_URL => 'http://example.com/',
                ],
                'expectedSiteRootUrl' => 'http://example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'options' => [],
                    ],
                    [
                        'type' => [
                            'name' => 'Link integrity',
                        ],
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'test type options present, valid and invalid values' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [],
                'requestRequest' => [
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
                'requestAttributes' => [
                    StartRequestFactory::PARAMETER_SITE_ROOT_URL => 'http://foo.example.com/',
                ],
                'expectedSiteRootUrl' => 'http://foo.example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'options' => [
                            'html-validation-foo' => 'html-validation-bar',
                        ],
                    ],
                    [
                        'type' => [
                            'name' => 'Link integrity',
                        ],
                        'options' => [
                            'link-integrity-foo' => 'link-integrity-bar',
                        ],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'job parameters not an array' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [],
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                    ],
                    StartRequestFactory::PARAMETER_JOB_PARAMETERS => 'foo',
                ],
                'requestAttributes' => [
                    StartRequestFactory::PARAMETER_SITE_ROOT_URL => 'http://foo.example.com/',
                ],
                'expectedSiteRootUrl' => 'http://foo.example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'job parameters present and empty' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [],
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                    ],
                    StartRequestFactory::PARAMETER_JOB_PARAMETERS => [],
                ],
                'requestAttributes' => [
                    StartRequestFactory::PARAMETER_SITE_ROOT_URL => 'http://foo.example.com/',
                ],
                'expectedSiteRootUrl' => 'http://foo.example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [],
            ],
            'job parameters present' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [],
                'requestRequest' => [
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                    ],
                    StartRequestFactory::PARAMETER_JOB_PARAMETERS => [
                    'job-parameter-foo' => 'job-parameter-bar',
                    'a%20b' => 'foobar',
                    ],
                ],
                'requestAttributes' => [
                    StartRequestFactory::PARAMETER_SITE_ROOT_URL => 'http://foo.example.com/',
                ],
                'expectedSiteRootUrl' => 'http://foo.example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [
                    'job-parameter-foo' => 'job-parameter-bar',
                    'a b' => 'foobar',
                ],
            ],
            'job parameters present, as GET request' => [
                'userEmail' => 'user@example.com',
                'requestQuery' => [
                    StartRequestFactory::PARAMETER_TEST_TYPES => [
                        'html validation',
                    ],
                    StartRequestFactory::PARAMETER_JOB_PARAMETERS => [
                        'job-parameter-foo' => 'job-parameter-bar',
                        'a%20b' => 'foobar',
                    ],
                ],
                'requestRequest' => [],
                'requestAttributes' => [
                    StartRequestFactory::PARAMETER_SITE_ROOT_URL => 'http://foo.example.com/',
                ],
                'expectedSiteRootUrl' => 'http://foo.example.com/',
                'expectedTaskConfigurationCollection' => [
                    [
                        'type' => [
                            'name' => 'HTML validation',
                        ],
                        'options' => [],
                    ],
                ],
                'expectedJobParameters' => [
                    'job-parameter-foo' => 'job-parameter-bar',
                    'a b' => 'foobar',
                ],
            ],
        ];
    }

    /**
     * @param User $user
     */
    private function setUser(User $user)
    {
        $securityTokenStorage = $this->container->get('security.token_storage');

        /* @var MockInterface|TokenInterface */
        $token = \Mockery::mock(TokenInterface::class);
        $token
            ->shouldReceive('getUser')
            ->andReturn($user);

        $securityTokenStorage->setToken($token);
    }
}
