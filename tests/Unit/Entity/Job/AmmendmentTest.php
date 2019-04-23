<?php

namespace App\Tests\Unit\Entity\Job;

use App\Entity\Job\Ammendment;
use App\Tests\Factory\ModelFactory;

class AmmendmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param Ammendment $ammendment
     * @param array $expectedSerializedData
     */
    public function testJsonSerialize(Ammendment $ammendment, $expectedSerializedData)
    {
        $this->assertEquals($expectedSerializedData, $ammendment->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        return [
            'default' => [
                'rejectionReason' => ModelFactory::createAmmendment([
                    ModelFactory::AMMENDMENT_REASON => 'reason-name',
                    ModelFactory::AMMENDMENT_CONSTRAINT => ModelFactory::createAccountPlanConstraint([
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
        ];
    }
}
