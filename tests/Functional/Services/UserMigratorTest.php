<?php

namespace App\Tests\Functional\Services;

use App\Entity\User;
use App\Entity\UserAccountPlan;
use App\Services\UserDataProvider;
use App\Services\UserMigrator;
use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class UserMigratorTest extends AbstractBaseTestCase
{
    /**
     * @var UserMigrator
     */
    private $userMigrator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $repository;

    /**
     * @var array
     */
    private $userData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userMigrator = self::$container->get(UserMigrator::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);

        $this->repository = $this->entityManager->getRepository(User::class);

        $this->removeAllForEntity(UserAccountPlan::class);
        $this->removeAllForEntity(User::class);

        $this->entityManager->flush();

        $usersDataProvider = self::$container->get(UserDataProvider::class);
        $this->userData = $usersDataProvider->getData();
    }

    public function testMigrateFromEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $this->userMigrator->migrate();

        $this->assertRepositoryUsers($this->userData);
    }

    public function testMigrateFromNonEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $userData = current($this->userData);

        $reflectionMethod = new \ReflectionMethod(UserMigrator::class, 'migrateUser');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->userMigrator, $userData);

        $this->assertRepositoryUsers([
            $userData,
        ]);

        $this->userMigrator->migrate();
        $this->assertRepositoryUsers($this->userData);
    }

    private function assertRepositoryUsers(array $userDataCollection)
    {
        $repositoryUsers = $this->repository->findAll();

        $this->assertCount(count($userDataCollection), $repositoryUsers);

        /* @var User[] $users */
        $users = [];

        foreach ($repositoryUsers as $user) {
            $users[$user->getEmail()] = $user;
        }

        foreach ($userDataCollection as $userData) {
            $user = $users[$userData['email']];

            $this->assertEquals($userData['email'], $user->getEmail());
            $this->assertEquals($userData['username'], $user->getUsername());
            $this->assertNotNull($user->getPassword());
        }
    }

    private function removeAllForEntity(string $entityClass)
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $entities = $repository->findAll();
        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
        }
    }
}
