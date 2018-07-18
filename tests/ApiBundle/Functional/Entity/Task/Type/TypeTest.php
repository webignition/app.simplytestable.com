<?php

namespace Tests\ApiBundle\Functional\Entity\Task\Type;

use SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;

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
