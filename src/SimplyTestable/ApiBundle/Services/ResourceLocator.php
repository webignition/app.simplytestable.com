<?php
namespace SimplyTestable\ApiBundle\Services;

use Symfony\Component\HttpKernel\KernelInterface;

class ResourceLocator
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param $name
     *
     * @return array|string
     */
    public function locate($name)
    {
        return $this->kernel->locateResource(
            $name
        );
    }
}
