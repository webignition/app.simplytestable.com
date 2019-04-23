<?php

namespace App\Tests\Unit\Entity\Job;

use App\Entity\Job\RejectionReason;
use App\Tests\Factory\ModelFactory;

class RejectionReasonTest extends \PHPUnit\Framework\TestCase
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
            'with constraint' => [
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
                    ],
                ],
            ],
            'without constraint' => [
                'rejectionReason' => ModelFactory::createRejectionReason([
                    ModelFactory::REJECTION_REASON_REASON => 'reason-name',
                ]),
                'expectedSerializedData' => [
                    'reason' => 'reason-name',
                ],
            ],
        ];
    }
}
