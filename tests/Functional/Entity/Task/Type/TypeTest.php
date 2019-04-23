<?php

namespace App\Tests\Functional\Entity\Task\Type;

use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Task\Type\Type;

class TypeTest extends AbstractBaseTestCase
{
    public function testPersistAndRetrieve()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskTypeRepository = $entityManager->getRepository(Type::class);

        $name = 'name-ɸ';
        $description = 'description-ɸ';

        $type = new Type();
        $type->setName($name);
        $type->setDescription($description);

        $entityManager->persist($type);
        $entityManager->flush();

        $typeId = $type->getId();

        $entityManager->clear();

        $retrievedTaskType = $taskTypeRepository->find($typeId);

        $this->assertEquals($name, $retrievedTaskType->getName());
        $this->assertEquals($description, $retrievedTaskType->getDescription());
    }
}
