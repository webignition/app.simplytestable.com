<?php

namespace App\Tests\Functional\Entity\Task;

use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Task\TaskType;

class TypeTest extends AbstractBaseTestCase
{
    public function testPersistAndRetrieve()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskTypeRepository = $entityManager->getRepository(TaskType::class);

        $name = 'name-ɸ';
        $description = 'description-ɸ';

        $type = new TaskType();
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
