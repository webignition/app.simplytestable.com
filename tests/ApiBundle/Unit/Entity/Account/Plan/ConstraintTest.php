<?php

namespace Tests\ApiBundle\Unit\Entity\Account\Plan;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;

class ConstraintTest extends \PHPUnit\Framework\TestCase
{
    public function testJsonSerialize()
    {
        $constraint = new Constraint();

        $constraint->setName('constraint-name');
        $constraint->setLimit(20);
        $constraint->setIsAvailable(true);

        $this->assertEquals(
            [
                'name' => $constraint->getName(),
                'limit' => $constraint->getLimit(),
                'is_available' => $constraint->getIsAvailable(),
            ],
            $constraint->jsonSerialize()
        );
    }
}
