<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Services;

use App\Entity\Job\Type as JobType;
use App\Entity\User;
use App\Entity\WebSite;
use App\Request\Job\StartRequest;
use App\Services\JobConfigurationFactory;
use App\Services\JobTypeService;
use App\Services\TaskTypeService;
use App\Tests\Factory\ModelFactory;
use App\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

class JobConfigurationFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var JobConfigurationFactory
     */
    private $jobConfigurationFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobConfigurationFactory = new JobConfigurationFactory();
    }

    /**
     * @dataProvider createFromJobStartRequestDataProvider
     */
    public function testCreateFromJobStartRequest(
        StartRequest $startRequest,
        User $expectedUser,
        WebSite $expectedWebsite,
        JobType $expectedJobType,
        TaskConfigurationCollection $expectedTaskConfigurationCollection,
        string $expectedParameters
    ) {
        $jobConfiguration = $this->jobConfigurationFactory->createFromJobStartRequest($startRequest);

        $expectedTaskConfigurationCollection->rewind();
        $jobConfiguration->getTaskConfigurationCollection()->rewind();

        $this->assertSame($expectedUser, $jobConfiguration->getUser());
        $this->assertSame($expectedWebsite, $jobConfiguration->getWebsite());
        $this->assertSame($expectedJobType, $jobConfiguration->getType());
        $this->assertEquals(
            $expectedTaskConfigurationCollection,
            $jobConfiguration->getTaskConfigurationCollection()
        );
        $this->assertEquals($expectedParameters, $jobConfiguration->getParameters());
    }

    public function createFromJobStartRequestDataProvider(): array
    {
        $user = ModelFactory::createUser([
            ModelFactory::USER_EMAIL => 'user@example.com',
        ]);

        $website = ModelFactory::createWebsite([
            ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
        ]);

        $jobType = ModelFactory::createJobType([
            ModelFactory::JOB_TYPE_NAME => JobTypeService::FULL_SITE_NAME,
        ]);

        $emptyTaskConfigurationCollection = new TaskConfigurationCollection();
        $nonEmptyTaskConfigurationCollection = ModelFactory::createTaskConfigurationCollection([
            [
                ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::HTML_VALIDATION_TYPE,
                ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                    'html-validation-foo' => 'html-validation-bar',
                ],
            ],
            [
                ModelFactory::TASK_CONFIGURATION_TYPE => TaskTypeService::LINK_INTEGRITY_TYPE,
                ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                    'link-integrity-foo' => 'link-integrity-bar',
                ],
            ],
        ]);

        return [
            'empty parameters' => [
                'startRequest' => new StartRequest($user, $website, $jobType, $emptyTaskConfigurationCollection, []),
                'expectedUser' => $user,
                'expectedWebsite' => $website,
                'expectedJobType' => $jobType,
                'expectedTaskConfigurationCollection' => $emptyTaskConfigurationCollection,
                'expectedParameters' => '[]',
            ],
            'non-empty parameters' => [
                'startRequest' => new StartRequest($user, $website, $jobType, $emptyTaskConfigurationCollection, [
                    'foo' => 'bar',
                ]),
                'expectedUser' => $user,
                'expectedWebsite' => $website,
                'expectedJobType' => $jobType,
                'expectedTaskConfigurationCollection' => $emptyTaskConfigurationCollection,
                'expectedParameters' => json_encode(['foo' => 'bar']),
            ],
            'task configuration collection' => [
                'startRequest' => new StartRequest($user, $website, $jobType, $nonEmptyTaskConfigurationCollection, []),
                'expectedUser' => $user,
                'expectedWebsite' => $website,
                'expectedJobType' => $jobType,
                'expectedTaskConfigurationCollection' => $nonEmptyTaskConfigurationCollection,
                'expectedParameters' => '[]',
            ],
        ];
    }
}
