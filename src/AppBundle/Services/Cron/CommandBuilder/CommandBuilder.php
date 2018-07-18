<?php
namespace AppBundle\Services\Cron\CommandBuilder;

use Symfony\Component\Process\PhpExecutableFinder;
use Cron\CronBundle\Cron\CommandBuilder as BaseCommandBuilder;

class CommandBuilder extends BaseCommandBuilder
{
    const PATTERN_MODIFIER = '/ #.+$/';
    const MODIFIER_PREFIX = ' #';
    const CONSOLE_PATH = 'bin/console';

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $phpExecutable;


    /**
     * @var string
     */
    private $command;

    /**
     * @param string $environment
     */
    public function __construct($environment)
    {
        $this->environment = $environment;

        $finder = new PhpExecutableFinder();
        $this->phpExecutable = $finder->find();
    }

    /**
     * @param string $command
     *
     * @return string
     */
    public function build($command)
    {
        $this->command = $command;

        if (!$this->commandHasModifier()) {
            return sprintf(
                '%s %s %s --env=%s',
                $this->phpExecutable,
                self::CONSOLE_PATH,
                $this->command,
                $this->environment
            );
        }

        return sprintf(
            '%s && %s %s %s --env=%s',
            $this->getModifier(),
            $this->phpExecutable,
            self::CONSOLE_PATH,
            $this->getUnmodifiedCommand(),
            $this->environment
        );
    }


    private function commandHasModifier() {
        return preg_match(self::PATTERN_MODIFIER, $this->command) > 0;
    }


    /**
     * @return string
     */
    private function getModifier() {
        $modifierMatches = [];

        preg_match(self::PATTERN_MODIFIER, $this->command, $modifierMatches);

        return ltrim($modifierMatches[0], ' #');
    }


    /**
     * @return string
     */
    private function getUnmodifiedCommand() {
        $modifier = $this->getModifier();
        return substr($this->command, 0, (strlen($this->command) - strlen($modifier) - strlen(self::MODIFIER_PREFIX)));
    }
}
