<?php

namespace App\Tests\Unit\Command\Migrate;

use App\Command\Migrate\ExpirePublicUserJobs;
use App\Repository\JobRepository;
use App\Services\JobService;
use App\Services\StateService;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\LockInterface;

class ExpirePublicUserJobsTest extends \PHPUnit\Framework\TestCase
{
    public function testRunUnableToAcquireLock()
    {
        $lock = \Mockery::mock(LockInterface::class);
        $lock
            ->shouldReceive('acquire')
            ->andReturnFalse();

        $lockFactory = \Mockery::mock(Factory::class);
        $lockFactory
            ->shouldReceive('createLock')
            ->with(ExpirePublicUserJobs::LOCK_KEY, ExpirePublicUserJobs::LOCK_TTL)
            ->andReturn($lock);

        $command = new ExpirePublicUserJobs(
            \Mockery::mock(EntityManagerInterface::class),
            \Mockery::mock(JobRepository::class),
            \Mockery::mock(UserService::class),
            \Mockery::mock(StateService::class),
            \Mockery::mock(JobService::class),
            $lockFactory
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(ExpirePublicUserJobs::RETURN_CODE_UNABLE_TO_ACQUIRE_LOCK, $returnCode);
    }
}
