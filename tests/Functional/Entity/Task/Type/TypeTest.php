<?php

namespace App\Tests\Functional\Entity\Task\Type;

use App\Entity\Task\Type\TaskTypeClass;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Task\Type\Type;

class TypeTest extends AbstractBaseTestCase
{
    public function testPersistAndRetrieve()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskTypeClassRepository = $entityManager->getRepository(TaskTypeClass::class);
        $taskTypeRepository = $entityManager->getRepository(Type::class);

        $name = 'name-ɸ';
        $description = 'description-ɸ';

        /* @var TaskTypeClass $taskTypeClass */
        $taskTypeClass = $taskTypeClassRepository->find(1);

        $type = new Type();
        $type->setName($name);
        $type->setDescription($description);
        $type->setClass($taskTypeClass);

        $entityManager->persist($type);
        $entityManager->flush();

        $typeId = $type->getId();

        $entityManager->clear();

        $retrievedTaskType = $taskTypeRepository->find($typeId);

        $this->assertEquals($name, $retrievedTaskType->getName());
        $this->assertEquals($description, $retrievedTaskType->getDescription());
    }
}
