<?php
namespace SimplyTestable\ApiBundle\Command\Reporting;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use SimplyTestable\ApiBundle\Command\BaseCommand;
use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

use webignition\HtmlValidationErrorNormaliser\HtmlValidationErrorNormaliser;


class GetTopErrorsCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_INVALID_TASK_TYPE = 2;    
    const DEFAULT_REPORT_LIMIT = 100;
    
    /**
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;
    
    
    /**
     *
     * @var TaskType
     */
    private $taskType;
    
    
    /**
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;
    
    /**
     *
     * @var \Doctrine\ORM\EntityRepository
     */
    private $taskOutputRepository; 
    
    
    private $messages = array();
    
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:get-top-errors')
            ->setDescription('Generate top errors report by task type')
            ->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'Run through the process without writing any data')
            ->addOption('task-type', null, InputOption::VALUE_REQUIRED, 'Name of task type for which to generate report')
            ->addOption('task-output-limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number of task outputs processed')
            ->addOption('report-limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number lines in the report')            
            ->setHelp(<<<EOF
Generate top errors report by task type.
EOF
        );     
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        $this->input = $input;
        
        if ($this->isDryRun()) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }
        
        $output->write('<info>Requested task type: '.$input->getOption('task-type').' ... </info>');        
        $this->taskType = $this->getTaskTypeService()->getByName($input->getOption('task-type'));
        
        if (is_null($this->taskType)) {
            $output->writeln('invalid task type');
            return self::RETURN_CODE_INVALID_TASK_TYPE;
        }
        
        $output->writeln('ok');
        
        $limit = $this->getTaskOutputLimit();
        $output->write('<info>Requested limit: ');
        
        if (is_null($limit)) {
            $output->writeln('NONE');
        } else {
            $output->writeln($limit);
        }        
        
        $output->writeln('');
        
        $output->write('Finding task output for ['.$this->taskType->getName().'] tasks ... ');
        
        $taskOutputIds = $this->getTaskOutputRepository()->findIdsByTaskType($this->taskType, $limit);
        $taskOutputCount = count($taskOutputIds);
        
        $output->writeln('['.$taskOutputCount.'] task outputs found');
        
        $processedTaskOutputCount = 0;
        
        $normaliser = new HtmlValidationErrorNormaliser();
        
        foreach ($taskOutputIds as $taskOutputId) {
            $processedTaskOutputCount++;
            $output->writeln('Processing task output ['.$taskOutputId.'] ['.$processedTaskOutputCount.' of '.$taskOutputCount.']');
            
            $taskOutput = $this->getTaskOutputRepository()->find($taskOutputId);
            
            $messages = $this->getMessagesForTaskOutput($taskOutput);
            
            foreach ($messages as $message) {                
                $normalisationResult = $normaliser->normalise($message);                
                $messageToStore = $normalisationResult->isNormalised() ? $normalisationResult->getNormalisedError()->getNormalForm() : $message;

                if (!array_key_exists($messageToStore, $this->messages)) {
                    $this->messages[$messageToStore] = array(
                        'frequency' => 0,
                        'normalised' => $normalisationResult->isNormalised()
                    );
                    
                    if ($normalisationResult->isNormalised()) {
                        $this->messages[$messageToStore]['parameters'] = array();
                    }                    
                }
                
                $this->messages[$messageToStore]['frequency']++;
                
                if ($normalisationResult->isNormalised()) {
//                    if ($normalisationResult->getNormalisedError()->getNormalForm() == 'value of attribute "%0" cannot be ""%1"%0"') {
//                        var_dump($message);
//                        exit();
//                    }
                            
                    // value of attribute "%0" cannot be ""%1"%0"
                    
                    foreach ($normalisationResult->getNormalisedError()->getParameters() as $position => $value) {
                        $value = strtolower($value);
                        
                        if (!isset($this->messages[$messageToStore]['parameters'][$position])) {
                            $this->messages[$messageToStore]['parameters'][$position] = array();
                        }
                        
                        if (count($this->messages[$messageToStore]['parameters'][$position]) <= 10) {
                            if (!isset($this->messages[$messageToStore]['parameters'][$position][$value])) {
                                $this->messages[$messageToStore]['parameters'][$position][$value] = 0;
                            }

                                                         
                        }
                        
                        if (isset($this->messages[$messageToStore]['parameters'][$position][$value])) {
                            $this->messages[$messageToStore]['parameters'][$position][$value]++;
                        }                        
                        
                        //$this->messages[$messageToStore]['parameters'][$position][$value]++;
                        
                       
                    }
                    
                    //var_dump($normalisationResult->getNormalisedError()->getParameters());
                    //exit();
                }
                
//                /exit();

            }
            
            $this->getEntityManager()->detach($taskOutput);
        }
        
        $output->writeln('');
        $output->writeln('<info>============================================</info>');
        $output->writeln('');
        $output->writeln('Total messages analysed: ' . count($this->messages));
        $output->writeln('');
        
        arsort($this->messages);
        
        $this->messages = array_slice($this->messages, 0, $this->getReportLimit());
        
        foreach ($this->messages as $message => $messageStatistics) {
            if ($messageStatistics['frequency'] > 1) {
                $reportLine = $messageStatistics['frequency'] . "\t" . ($messageStatistics['normalised'] ? 'N' : 'R') . "\t" . $message;
                
                $parametersSection = '';
                
                if ($messageStatistics['normalised']) {
                    $parametersSection = ' (';
                    
                    foreach ($messageStatistics['parameters'] as $position => $valueStatistics) {
                        if ($position === 0) {
//                            var_dump($valueStatistics);
//                            
//                            arsort($valueStatistics);
////                            
////                            
//                            var_dump($valueStatistics);
//                            exit();                            
                            
                            arsort($valueStatistics);
                            
                            $keyValuePairs = array();
                            
                            foreach ($valueStatistics as $key => $value) {
                                $keyValuePairs[] = $key.':'.$value;
                            }
                            
                            $parametersSection .= implode(', ', $keyValuePairs);
                            
//                            if (isset($valueStatistics['sizes']) && $valueStatistics['sizes'] === 5) {

//                            }
                            

                        }
                    }
                    
                    $parametersSection .= ')';
                    
//                    var_dump($messageStatistics['parameters']);
//                    exit();
                }
                
                $output->writeln($reportLine .  $parametersSection);
            }
        }        
        
        return self::RETURN_CODE_OK;
    }    
    
    /**
     * 
     * @return boolean
     */
    private function isDryRun() {        
        return $this->input->getOption('dry-run') == 'true';
    }
    
    /**
     * 
     * @return int
     */
    private function getTaskOutputLimit() {
        $limit = (int)$this->input->getOption('task-output-limit');        
        return ($limit > 0) ? $limit : null;
    }    
    
    
    /**
     * 
     * @return int
     */
    private function getReportLimit() {
        $limit = (int)$this->input->getOption('report-limit');        
        return ($limit > 0) ? $limit : self::DEFAULT_REPORT_LIMIT;
    }
    
    
    private function getMessagesForTaskOutput(TaskOutput $taskOutput) {
        $messages = array();
        
        if ($taskOutput->getErrorCount() === 0) {
            return $messages;
        }
        
        switch ($this->taskType->getName()) {
            case 'HTML validation':
                $decodedOutput = json_decode($taskOutput->getOutput());
                
                if ($decodedOutput instanceof \stdClass) {
                    foreach ($decodedOutput->messages as $message) {
                        if ($message->type == 'error') {
                            $messages[] = $message->message;   
                        }                                   
                    }                    
                }
                
                break;
        }
        
        return $messages;      
    }
    
    // reference to entity "order" for which no system identifier could be generated
    
    private function getGenericHtmlError($htmlErrorString) {
        
        
        return $htmlErrorString;
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\TaskTypeService
     */    
    private function getTaskTypeService() {
        return $this->getContainer()->get('simplytestable.services.tasktypeservice');
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
     * @return \SimplyTestable\ApiBundle\Repository\TaskOutputRepository
     */
    private function getTaskOutputRepository() {
        if (is_null($this->taskOutputRepository)) {
            $this->taskOutputRepository = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output');
        }
        
        return $this->taskOutputRepository;
    }  
}