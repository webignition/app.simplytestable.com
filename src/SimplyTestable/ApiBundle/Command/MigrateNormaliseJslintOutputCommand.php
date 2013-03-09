<?php
namespace SimplyTestable\ApiBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\WebClientBundle\Entity\Task\Output as TaskOutput;

class MigrateNormaliseJsLintOutputCommand extends BaseCommand
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
            ->setName('simplytestable:normalise-jslint-output')
            ->setDescription('Normalise the tmp paths in JSLint output and truncate JSLint fragment lines to 256 characters')
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
        
        $output->writeln('Finding jslint output ...');        
        // "statusLine":"\/tmp\/d64dc5dca841a048946621b935e540a3:13060:1358494120.1354"
        
        $jsStaticAnalysisType = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Type')->findOneBy(array(
            'name' => 'JS static analysis'
        ));
        
        $jsLintOutputIds = $this->getTaskRepository()->getTaskOutputByType($jsStaticAnalysisType);
        
        $output->writeln('['.count($jsLintOutputIds).'] outputs to examine');
        
        $jsLintOutputCount = count($jsLintOutputIds);
        $processedJsLintOutputCount = 0;
        
        $totalTmpReferenceCount = 0;
        $totalFragmentLengthFixCount = 0;
        $totalReduction = 0;

        foreach ($jsLintOutputIds as $jsLintOutputId) {
            $processedJsLintOutputCount++;            
            $output->writeln('Examining ' . $jsLintOutputId.' ('.($jsLintOutputCount - $processedJsLintOutputCount).' remaining)');
            
            /* @var $taskOutput TaskOutput */
            $taskOutput = $this->getTaskOutputRepository()->findOneBy(array(
                'id' => (int)$jsLintOutputId
            ));
            
            $beforeLength = strlen($taskOutput->getOutput());
            
            $jsLintObject = json_decode($taskOutput->getOutput());
            
            if (is_int($jsLintObject)) {
                continue;
            }
            
            $matches = array();
            
            $tmpReferenceFixCount = 0;
            $fragmentLengthFixCount = 0;
            
            if (preg_match_all('/"statusLine":"\\\\\/tmp\\\\\/[a-z0-9]{32}:[0-9]+:[0-9]+\.[0-9]+/', $taskOutput->getOutput(), $matches)) {                
                $output->write('    Fixing tmp file references ['.count($matches[0]).'] ... ');
                
                foreach ($jsLintObject as $sourcePath => $sourcePathOutput) {                    
                    // \/tmp\/b8d64d0bc142b3f670cc0611b0aebcae:113:1358342896.7425
                    // \/tmp\/dac78adf714a30493e2def48b5234dcf:308:1358373039.394 is OK
                    if (preg_match('/^\/tmp\/[a-z0-9]{32}:[0-9]+:[0-9]+\.[0-9]+$/', $sourcePathOutput->statusLine)) {
                        $sourcePathOutput->statusLine = substr($sourcePathOutput->statusLine, 0, strpos($sourcePathOutput->statusLine, ':'));
                        $tmpReferenceFixCount++;
                    }
                    
                    if (preg_match('/^\/tmp\/[a-z0-9]{32}:[0-9]+:[0-9]+\.[0-9]+ is OK.$/', $sourcePathOutput->statusLine)) {
                        $sourcePathOutput->statusLine = substr($sourcePathOutput->statusLine, 0, strpos($sourcePathOutput->statusLine, ':'));
                        $tmpReferenceFixCount++;
                    }                    
                    
                }                
                
                $output->writeln('fixed '.$tmpReferenceFixCount.' tmp file references');                
            }
            
            $output->write('    Fixing fragment lengths ... ');
            
            foreach ($jsLintObject as $sourcePath => $sourcePathOutput) {
                if (isset($sourcePathOutput->entries)) {
                    foreach ($sourcePathOutput->entries as $entry) {
                        if (strlen($entry->fragmentLine->fragment) > 256) {
                            $entry->fragmentLine->fragment = substr($entry->fragmentLine->fragment, 0, 256);
                            $fragmentLengthFixCount++;
                        }
                    }                    
                }
            }            
            $output->writeln('fixed '.$fragmentLengthFixCount.' fragment lengths');
            
            if ($tmpReferenceFixCount > 0 || $fragmentLengthFixCount > 0) {
                $totalTmpReferenceCount += $tmpReferenceFixCount;
                $totalFragmentLengthFixCount += $fragmentLengthFixCount;
                
                $taskOutput->setOutput(json_encode($jsLintObject));                
                $taskOutput->generateHash();
                
                $afterLength = strlen($taskOutput->getOutput());
                
                $reduction = $beforeLength - $afterLength;
                $totalReduction += $reduction;
                
                $reductionInK = round($reduction / (1024), 2);
                $reductionInM = round($reduction / (1024 * 1024), 2);
                $reductionInG = round($reduction / (1024 * 1024 * 1024), 2);
                
                if ($reductionInG > 1) {
                    $output->writeln('    Reduced output by '.$reductionInG.'Gb');
                } elseif ($reductionInM > 1) {
                    $output->writeln('    Reduced output by '.$reductionInM.'Mb');
                } else {
                    $output->writeln('    Reduced output by '.$reductionInK.'Kb');
                }                
                
                if (!$this->isDryRun($input)) {
                    $this->entityManager->persist($taskOutput);
                    $this->entityManager->flush();
                }
            }
            
            $this->entityManager->detach($taskOutput);
        }
        
        $output->writeln('==========================================');
        
        $output->writeln('Fixed '.$totalTmpReferenceCount.' tmp references');
        $output->writeln('Fixed '.$totalFragmentLengthFixCount.' fragment lengths');        
        
        $totalReductionInK = round($totalReduction / (1024), 2);
        $totalReductionInM = round($totalReduction / (1024 * 1024), 2);
        $totalReductionInG = round($totalReduction / (1024 * 1024 * 1024), 2);

        if ($totalReductionInG > 1) {
            $output->writeln('Reduced total output by '.$totalReductionInG.'Gb');
        } elseif ($totalReductionInM > 1) {
            $output->writeln('Reduced total output by '.$totalReductionInM.'Mb');
        } else {
            $output->writeln('Reduced total output by '.$totalReductionInK.'Kb');
        }                
        
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