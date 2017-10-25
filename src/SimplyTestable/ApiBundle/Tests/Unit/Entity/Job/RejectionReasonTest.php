<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Entity\Job;

use SimplyTestable\ApiBundle\Entity\Job\RejectionReason;
use SimplyTestable\ApiBundle\Tests\Factory\ModelFactory;

class RejectionReasonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param RejectionReason $rejectionReason
     * @param array $expectedSerializedData
     */
    public function testJsonSerialize(RejectionReason $rejectionReason, $expectedSerializedData)
    {
        $this->assertEquals($expectedSerializedData, $rejectionReason->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        return [
            'default' => [
                'rejectionReason' => ModelFactory::createRejectionReason([
                    ModelFactory::REJECTION_REASON_REASON => 'reason-name',
                    ModelFactory::REJECTION_REASON_CONSTRAINT => ModelFactory::createAccountPlanConstraint([
                        ModelFactory::CONSTRAINT_NAME => 'constraint-name',
                        ModelFactory::CONSTRAINT_LIMIT => 10,
                    ]),
                ]),
                'expectedSerializedData' => [
                    'reason' => 'reason-name',
                    'constraint' => [
                        'name' => 'constraint-name',
                        'limit' => 10,
                        'is_available' => true,
                    ],
                ],
            ],
        ];
    }
}
