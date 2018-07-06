<?php

namespace Tests\ApiBundle\Unit\Entity\Job;

use SimplyTestable\ApiBundle\Entity\Job\Configuration;
use Tests\ApiBundle\Factory\ModelFactory;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param Configuration $configuration
     * @param array $expectedSerializedData
     */
    public function testJsonSerialize(Configuration $configuration, $expectedSerializedData)
    {
        $this->assertEquals($expectedSerializedData, $configuration->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        return [
            'without task configuration' => [
                'configuration' => ModelFactory::createJobConfiguration([
                    ModelFactory::JOB_CONFIGURATION_LABEL => 'foo',
                    ModelFactory::JOB_CONFIGURATION_USER => ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                    ModelFactory::JOB_CONFIGURATION_WEBSITE => ModelFactory::createWebsite([
                        ModelFactory::WEBSITE_CANONICAL_URL => 'http://foo.example.com/',
                    ]),
                    ModelFactory::JOB_CONFIGURATION_TYPE => ModelFactory::createJobType([
                        ModelFactory::JOB_TYPE_NAME => 'job type name',
                    ]),
                    ModelFactory::JOB_CONFIGURATION_PARAMETERS => 'parameters string'
                ]),
                'expectedSerializedData' => [
                    'label' => 'foo',
                    'user' => 'user@example.com',
                    'website' => 'http://foo.example.com/',
                    'type' => 'job type name',
                    'task_configurations' => [],
                    'parameters' => 'parameters string',
                ],
            ],
            'with task configuration' => [
                'configuration' => ModelFactory::createJobConfiguration([
                    ModelFactory::JOB_CONFIGURATION_LABEL => 'foo',
                    ModelFactory::JOB_CONFIGURATION_USER => ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                    ModelFactory::JOB_CONFIGURATION_WEBSITE => ModelFactory::createWebsite([
                        ModelFactory::WEBSITE_CANONICAL_URL => 'http://bar.example.com/',
                    ]),
                    ModelFactory::JOB_CONFIGURATION_TYPE => ModelFactory::createJobType([
                        ModelFactory::JOB_TYPE_NAME => 'job type name',
                    ]),
                    ModelFactory::JOB_CONFIGURATION_TASK_CONFIGURATION_COLLECTION => [
                        ModelFactory::createTaskConfiguration([
                            ModelFactory::TASK_CONFIGURATION_TYPE => 'html validation',
                            ModelFactory::TASK_CONFIGURATION_OPTIONS => [
                                'html-foo' => 'html-bar',
                            ],
                        ]),
                        ModelFactory::createTaskConfiguration([
                            ModelFactory::TASK_CONFIGURATION_TYPE => 'css validation',
                        ]),
                    ],
                ]),
                'expectedSerializedData' => [
                    'label' => 'foo',
                    'user' => 'user@example.com',
                    'website' => 'http://bar.example.com/',
                    'type' => 'job type name',
                    'task_configurations' => [
                        [
                            'type' => 'html validation',
                            'options' => [
                                'html-foo' => 'html-bar',
                            ],
                            'is_enabled' => true,
                        ],
                        [
                            'type' => 'css validation',
                            'options' => [],
                            'is_enabled' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
