<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Job;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Type;

class TypeTest extends BaseSimplyTestableTestCase
{
    public function testPersistAndRetrieve()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $name = 'test-ɸ';
        $description = 'ɸ';

        $type = new Type();
        $type->setDescription($description);
        $type->setName($name);

        $entityManager->persist($type);
        $entityManager->flush();

        $typeId = $type->getId();

        $entityManager->clear();

        $jobTypeRepository = $entityManager->getRepository(Type::class);

        $retrievedType = $jobTypeRepository->find($typeId);

        $this->assertEquals($name, $retrievedType->getName());
        $this->assertEquals($description, $retrievedType->getDescription());
    }
}
