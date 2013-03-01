<?php
namespace SimplyTestable\ApiBundle\Command\Backup;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends BaseCommand
{
    const LEVEL_ESSENTIAL = 'essential';
    const LEVEL_MINIMAL = 'minimal';    
    const DEFAULT_LEVEL = self::LEVEL_ESSENTIAL;
    
    private $levels = array(
        self::LEVEL_ESSENTIAL,
        self::LEVEL_MINIMAL
    );
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:backup:create')
            ->setDescription('Create an application-level backup')
            ->addOption('level', null, InputOption::VALUE_OPTIONAL, 'Choose the backup level: essential, minimal')
            ->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'Run through the process without writing any data')
            ->setHelp(<<<EOF
Create an application-level backup.
    
You can choose a level (or depth) of backup:

essential:
  Backs up the bare essential application configuration and data, covering:
  - app/config/parameters.yml
  - src/SimplyTestable/ApiBundle/Resources/config/parameters.yml
  - all fos_user entities

minimal:
  Backs up a minimal amount of data, covering:
  - all essential assets
  - all Job entities
  - all Task entities
  - all TaskOutputEntities, minus the output field contents
  - all Worker entities
  - all WebSite entities
EOF
        );     
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->hasValidLevelOption($input)) {
            $output->writeln('<error>Incorrect level, must be one of: '.  implode(',', $this->levels).'</error>');
            passthru('php app/console '.$this->getName().' --help');
        }
        
        if ($this->isDryRun($input)) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }
        
        $output->writeln('Using level: <info>'.$this->getLevelOption($input).'</info>');        
    }
    
    /**
     * 
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return int
     */
    private function isDryRun(InputInterface $input) {
        return $input->getOption('dry-run') == 'true';
    }
    
    /**
     * 
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return string
     */
    private function getLevelOption(InputInterface $input) {
        $level = strtolower($input->getOption('level'));
        if ($level == '') {
            return self::DEFAULT_LEVEL;
        }
        
        return in_array($level, $this->levels) ? $level : null;
    }
    
    
    /**
     * 
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return boolean
     */
    private function hasValidLevelOption(InputInterface $input) {
        return !is_null($this->getLevelOption($input));
    }
}