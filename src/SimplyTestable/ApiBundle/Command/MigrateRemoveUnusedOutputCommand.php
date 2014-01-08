<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Entity\Task\Task;

class MigrateRemoveUnusedOutputCommand extends BaseCommand
{
    const DEFAULT_FLUSH_THRESHOLD = 100;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;       
    
    
    /**
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Repository\TaskRepository
     */
    private $taskRepository;
    
    /**
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    private $taskOutputRepository;
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:migrate:remove-unused-output')
            ->setDescription('Remove output not linked to any task')
            ->addOption('limit')
            ->addOption('flush-threshold')
            ->addOption('dry-run')             
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            $output->writeln('In maintenance-read-only mode, I can\'t do that right now');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }          
        
        $output->writeln('Finding unused output ...');
        
        $unusedTaskOutputIds = $this->getTaskOutputRepository()->findUnusedIds($this->getLimit($input));
        
        if (count($unusedTaskOutputIds) === 0) {
            $output->writeln('No unused task outputs found. Done.');
            return true;
        }
        
        $output->writeln('['.count($unusedTaskOutputIds).'] outputs found');
        $processedTaskOutputCount = 0;
        
        $flushThreshold = $this->getFlushTreshold($input);
        $persistCount = 0;
        
        foreach ($unusedTaskOutputIds as $unusedTaskOutputId) {
            $taskOutputToRemove = $this->getTaskOutputRepository()->find($unusedTaskOutputId);
            
            $processedTaskOutputCount++;
            $output->writeln('Removing output ['.$unusedTaskOutputId.'] ('.(count($unusedTaskOutputIds) - $processedTaskOutputCount).' remaining)');
            
            $this->getEntityManager()->remove($taskOutputToRemove);
            $persistCount++; 
            
            if ($persistCount == $flushThreshold) {
                $output->writeln('***** Flushing *****');
                $persistCount = 0;
                
                if (!$this->isDryRun($input)) {
                    $this->getEntityManager()->flush();                    
                } 
            }
        }
        
            if ($persistCount > 0) {
                $output->writeln('***** Flushing *****');                
                if (!$this->isDryRun($input)) {
                    $this->getEntityManager()->flush();                    
                } 
            }        
        
        return true;
    }
    
    
    
    /**
     * 
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return int
     */
    private function getLimit(InputInterface $input) {
        if ($input->getOption('limit') === false) {
            return 0;
        }
        
        $limit = filter_var($input->getOption('limit'), FILTER_VALIDATE_INT);
        
        return ($limit <= 0) ? 0 : $limit;
    } 
    
/**
     * 
     * @param InputInterface $input
     * @return int
     */
    private function getFlushTreshold($input) {
        return $this->getIntegerOptionWithDefault($input, 'flush-threshold', self::DEFAULT_FLUSH_THRESHOLD);
    }
    
    
    /**
     * 
     * @param InputInterface $input
     * @return int
     */
    private function getIntegerOptionWithDefault($input, $optionName, $defaultValue) {
        $value = $input->getOption($optionName);
        if ($value <= 0) {
            return $defaultValue;
        }
        
        return (int)$value;
    }    
    
    
    /**
     * 
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @return int
     */
    private function isDryRun(InputInterface $input) {
        return $input->getOption('dry-run');
    }
    
    
    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager() {
        if (is_null($this->entityManager)) {
            $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        }
        
        return  $this->entityManager;
    }
    
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Repository\TaskRepository
     */
    private function getTaskRepository() {
        if (is_null($this->taskRepository)) {
            $this->taskRepository = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Task');
        }
        
        return $this->taskRepository;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Repository\TaskOutputRepository
     */
    private function getTaskOutputRepository() {
        if (is_null($this->taskOutputRepository)) {
            $this->taskOutputRepository = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output');
        }
        
        return $this->taskOutputRepository;
    }
}