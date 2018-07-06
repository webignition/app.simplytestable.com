<?php

namespace Tests\ApiBundle\Unit\Entity;

use SimplyTestable\ApiBundle\Entity\ScheduledJob;
use Tests\ApiBundle\Factory\ModelFactory;

class ScheduledJobTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param ScheduledJob $scheduledJob
     * @param array $expectedSerializedData
     */
    public function testJsonSerialize(ScheduledJob $scheduledJob, $expectedSerializedData)
    {
        $this->assertEquals($expectedSerializedData, $scheduledJob->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        return [
            'without cron modififer' => [
                'scheduledJob' => ModelFactory::createScheduledJob([
                    ModelFactory::SCHEDULED_JOB_ID => 1,
                    ModelFactory::SCHEDULED_JOB_JOB_CONFIGURATION => ModelFactory::createJobConfiguration([
                        ModelFactory::JOB_CONFIGURATION_LABEL => 'foo',
                    ]),
                    ModelFactory::SCHEDULED_JOB_SCHEDULE => '* * * * *',
                    ModelFactory::SCHEDULED_JOB_IS_RECURRING => false,
                ]),
                'expectedResponseData' => [
                    'id' => 1,
                    'jobconfiguration' => 'foo',
                    'schedule' => '* * * * *',
                    'isrecurring' => 0,
                ],
            ],
            'with cron modififer' => [
                'scheduledJob' => ModelFactory::createScheduledJob([
                    ModelFactory::SCHEDULED_JOB_ID => 1,
                    ModelFactory::SCHEDULED_JOB_JOB_CONFIGURATION => ModelFactory::createJobConfiguration([
                        ModelFactory::JOB_CONFIGURATION_LABEL => 'foo',
                    ]),
                    ModelFactory::SCHEDULED_JOB_SCHEDULE => '* * * * 1',
                    ModelFactory::SCHEDULED_JOB_IS_RECURRING => true,
                    ModelFactory::SCHEDULED_JOB_SCHEDULE_MODIFIER => 'bar',
                ]),
                'expectedResponseData' => [
                    'id' => 1,
                    'jobconfiguration' => 'foo',
                    'schedule' => '* * * * 1',
                    'isrecurring' => 1,
                    'schedule-modifier' => 'bar',
                ],
            ],
        ];
    }
}
