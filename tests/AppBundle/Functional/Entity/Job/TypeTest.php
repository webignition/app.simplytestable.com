<?php

namespace Tests\AppBundle\Functional\Entity\Job;

use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Entity\Job\Type;

class TypeTest extends AbstractBaseTestCase
{
    public function testPersistAndRetrieve()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobTypeRepository = $entityManager->getRepository(Type::class);

        $name = 'test-ɸ';
        $description = 'ɸ';

        $type = new Type();
        $type->setDescription($description);
        $type->setName($name);

        $entityManager->persist($type);
        $entityManager->flush();

        $typeId = $type->getId();

        $entityManager->clear();

        $retrievedType = $jobTypeRepository->find($typeId);

        $this->assertEquals($name, $retrievedType->getName());
        $this->assertEquals($description, $retrievedType->getDescription());
    }
}
