<?php

namespace Tests\ApiBundle\Functional\Entity\Job;

use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Type;

class TypeTest extends AbstractBaseTestCase
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