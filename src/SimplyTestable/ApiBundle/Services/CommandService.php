<?php
namespace SimplyTestable\ApiBundle\Services;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CommandService
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $commandClass
     * @param array $inputArray
     * @param BufferedOutput $output
     *
     * @return mixed
     */
    public function execute($commandClass, $inputArray = [], BufferedOutput $output = null)
    {
        $command = $this->get($commandClass);

        $input = new ArrayInput($inputArray);

        if (is_null($output)) {
            $output = new BufferedOutput();
        }

        return $command->execute($input, $output);
    }

    /**
     * @param string $commandClass
     *
     * @return ContainerAwareCommand
     */
    public function get($commandClass) {
        /* @var ContainerAwareCommand $command */
        $command = new $commandClass;
        $command->setContainer($this->container);

        return $command;
    }
}
