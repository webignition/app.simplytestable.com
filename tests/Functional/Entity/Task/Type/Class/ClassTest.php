<?php

namespace App\Tests\Functional\Entity\Task\Type;

use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Task\Type\TaskTypeClass;

class ClassTest extends AbstractBaseTestCase
{
    public function testPersistAndRetrieve()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $name = 'name-ɸ';
        $description = 'description-ɸ';

        $taskTypeClass = new TaskTypeClass();
        $taskTypeClass->setName($name);
        $taskTypeClass->setDescription($description);

        $entityManager->persist($taskTypeClass);
        $entityManager->flush();

        $taskTypeClassId = $taskTypeClass->getId();

        $entityManager->clear();

        $taskTypeClassRepository = $entityManager->getRepository(TaskTypeClass::class);

        $retrievedTaskType = $taskTypeClassRepository->find($taskTypeClassId);

        $this->assertEquals($name, $retrievedTaskType->getName());
        $this->assertEquals($description, $retrievedTaskType->getDescription());
    }
}
