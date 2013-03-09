<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Entity\Task\Task;

class MigrateCanonicaliseTaskOutputCommand extends BaseCommand
{
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
            ->setName('simplytestable:migrate:canonicalise-task-output')
            ->setDescription('Update tasks to point to canoical output')
            ->addOption('limit')
            ->addOption('dry-run')             
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {    
        if ($this->getApplicationStateService()->isInMaintenanceReadOnlyState()) {
            $output->writeln('In maintenance-read-only mode, I can\'t do that right now');
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }         
        
        $output->writeln('Finding duplicate output ...');
        
        $duplicateHashes = $this->getTaskOutputRepository()->findDuplicateHashes($this->getLimit($input));       
        
        if (count($duplicateHashes) === 0) {
            $output->writeln('No duplicate output found. Done.');
            return true;
        }
        
        $output->writeln('Processing ' . count($duplicateHashes) . ' duplicate hashes');
        $globalUpdatedTaskCount = 0;
        $updatedHashCount = 0;
        
        foreach ($duplicateHashes as $duplicateHash) {            
            $outputIds = $this->getTaskOutputRepository()->findIdsBy($duplicateHash);
            
            $updatedHashCount++;
            $output->writeln('['.(count($outputIds) - 1) . '] duplicates found for '.$duplicateHash.' ('.(count($duplicateHashes) - $updatedHashCount).' remaining)');
            
            $duplicateHashCount = count($outputIds) - 1;
            $processedDuplicateHashCount = 0;
            
            if (count($outputIds) > 1) {                
                $sourceId = $outputIds[0];
                $sourceOutput = $this->getTaskOutputRepository()->find($sourceId);
                $duplicatesToRemove = array_slice($outputIds, 1);
                $updatedTaskCount = 0;
                
                foreach ($duplicatesToRemove as $taskOutputId) {                    
                    $processedDuplicateHashCount++;
                    
                    $taskOutput = $this->getTaskOutputRepository()->find($taskOutputId);
                    
                    $tasksToUpdate = $this->getTaskRepository()->findBy(array(
                        'output' => $taskOutput
                    ));
                    
                    $duplicateHashTaskCount = count($tasksToUpdate);
                    $processedDuplicateHashTaskCount = 0;
                    
                    if (count($tasksToUpdate)) {
                        foreach ($tasksToUpdate as $task) {
                            $updatedTaskCount++;
                            $processedDuplicateHashTaskCount++;
                            
                            $output->writeln('Updating output for task ['.$task->getId().'] ('.($duplicateHashCount - $processedDuplicateHashCount).' batches remaining, '.($duplicateHashTaskCount - $processedDuplicateHashTaskCount).' tasks remaining in batch)');

                            if (!$this->isDryRun($input)) {
                                $task->setOutput($sourceOutput);
                                $this->getEntityManager()->persist($task);
                                $this->getEntityManager()->flush($task);                        
                            }                             
                        }
                    }                  
                }
                
                if ($updatedTaskCount === 0) {
                    $output->writeln('No tasks using duplicates of ' . $duplicateHash);
                }
                
                $globalUpdatedTaskCount += $updatedTaskCount;
                
                $output->writeln('');             
            }
        }
        
        $output->writeln('['.$globalUpdatedTaskCount.'] tasks updated');
        
        return true;
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
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager() {
        if (is_null($this->entityManager)) {
            $this->entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
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