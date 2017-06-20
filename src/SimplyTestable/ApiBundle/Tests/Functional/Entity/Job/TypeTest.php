<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Type;

class TypeTest extends BaseSimplyTestableTestCase {

    public function testUtf8Name() {
        $name = 'test-ɸ';

        $type = new Type();
        $type->setDescription('foo');
        $type->setName($name);

        $this->getManager()->persist($type);
        $this->getManager()->flush();

        $typeId = $type->getId();

        $this->getManager()->clear();

        $this->assertEquals($name, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\Type')->find($typeId)->getName());
    }

    public function testUtf8Description() {
        $description = 'ɸ';

        $type = new Type();
        $type->setDescription($description);
        $type->setName('test-foo');

        $this->getManager()->persist($type);
        $this->getManager()->flush();

        $typeId = $type->getId();

        $this->getManager()->clear();

        $this->assertEquals($description, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\Type')->find($typeId)->getDescription());
    }
}
