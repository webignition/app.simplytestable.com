<?php

namespace App\Tests\Unit\Entity\Account\Plan;

use App\Entity\Account\Plan\Constraint;

class ConstraintTest extends \PHPUnit\Framework\TestCase
{
    public function testJsonSerialize()
    {
        $constraint = new Constraint();

        $constraint->setName('constraint-name');
        $constraint->setLimit(20);

        $this->assertEquals(
            [
                'name' => $constraint->getName(),
                'limit' => $constraint->getLimit(),
            ],
            $constraint->jsonSerialize()
        );
    }
}
