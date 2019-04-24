<?php

namespace App\Tests\Functional\Services\FixtureLoader;

use App\Entity\User;
use App\Entity\UserAccountPlan;
use App\Services\UserDataProvider;
use App\Services\FixtureLoader\UserFixtureLoader;

class UserFixtureLoaderTest extends AbstractFixtureLoaderTest
{
    /**
     * @var UserFixtureLoader
     */
    private $userFixtureLoader;

    /**
     * @var array
     */
    private $userData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFixtureLoader = self::$container->get(UserFixtureLoader::class);
        $usersDataProvider = self::$container->get(UserDataProvider::class);
        $this->userData = $usersDataProvider->getData();
    }

    protected function getEntityClass(): string
    {
        return User::class;
    }

    protected function getEntityClassesToRemove(): array
    {
        return [
            UserAccountPlan::class,
            User::class,
        ];
    }

    public function testLoadFromEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $this->userFixtureLoader->load();

        $this->assertRepositoryUsers($this->userData);
    }

    public function testLoadFromNonEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $userData = current($this->userData);

        $reflectionMethod = new \ReflectionMethod(UserFixtureLoader::class, 'loadUser');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->userFixtureLoader, $userData);

        $this->assertRepositoryUsers([
            $userData,
        ]);

        $this->userFixtureLoader->load();
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
