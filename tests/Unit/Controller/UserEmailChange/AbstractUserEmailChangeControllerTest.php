<?php

namespace App\Tests\Unit\Controller\UserEmailChange;

use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\CanonicalizerInterface;
use App\Controller\UserEmailChangeController;
use App\Services\UserEmailChangeRequestService;
use App\Services\UserService;
use App\Tests\Factory\MockFactory;

abstract class AbstractUserEmailChangeControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $services
     *
     * @return UserEmailChangeController
     */
    protected function createUserEmailChangeController($services = [])
    {
        if (!isset($services[UserService::class])) {
            $services[UserService::class] = MockFactory::createUserService();
        }

        if (!isset($services[CanonicalizerInterface::class])) {
            $services[CanonicalizerInterface::class] = MockFactory::createCanonicalizer();
        }

        if (!isset($services[UserEmailChangeRequestService::class])) {
            $services[UserEmailChangeRequestService::class] = MockFactory::createUserEmailChangeRequestService();
        }

        if (!isset($services[EntityManagerInterface::class])) {
            $services[EntityManagerInterface::class] = MockFactory::createEntityManager();
        }

        $teamController = new UserEmailChangeController(
            $services[UserService::class],
            $services[CanonicalizerInterface::class],
            $services[UserEmailChangeRequestService::class],
            $services[EntityManagerInterface::class]
        );

        return $teamController;
    }
}
