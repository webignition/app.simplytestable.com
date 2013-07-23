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
    
    private $formatter = null;
    
    
    protected function configure()
    {
        $this
            ->setName('simplytestable:maintenance:get-top-errors')
            ->setDescription('Generate top errors report by task type')
            ->addOption('task-type', null, InputOption::VALUE_REQUIRED, 'Name of task type for which to generate report')
            ->addOption('task-output-limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number of task outputs processed')
            ->addOption('report-limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number lines in the report')            
            ->addOption('type-filter', null, InputOption::VALUE_OPTIONAL, 'Filter to normalised only (N) or non-normalised only (R)')
            ->addOption('normalise', null, InputOption::VALUE_OPTIONAL, 'Normalise error messages to common form?')
            ->setHelp(<<<EOF
Generate top errors report by task type.
EOF
        );     
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {   
        ini_set('xdebug.var_display_max_depth', '10'); 
        
        $this->input = $input;
        
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
        
        $messageCount = 0;
        
        foreach ($taskOutputIds as $taskOutputId) {
            $processedTaskOutputCount++;
            $output->writeln('Processing task output ['.$taskOutputId.'] ['.$processedTaskOutputCount.' of '.$taskOutputCount.']');
            
            $taskOutput = $this->getTaskOutputRepository()->find($taskOutputId);
            
            $messages = $this->getMessagesForTaskOutput($taskOutput);
            
            foreach ($messages as $message) {                
                $messageCount++;
                
                if ($this->shouldNormalise()) {
                    $normalisationResult = $normaliser->normalise($message);                
                    $messageToStore = $normalisationResult->isNormalised() ? $normalisationResult->getNormalisedError()->getNormalForm() : $message;                    
                } else {
                    $messageToStore = $message;
                }

                if (!array_key_exists($messageToStore, $this->messages)) {
                    $this->messages[$messageToStore] = array(
                        'count' => 0,
                        'normalised' => ($this->shouldNormalise()) ? $normalisationResult->isNormalised() : false
                    );
                    
                    if ($this->shouldNormalise() && $normalisationResult->isNormalised()) {                        
                        $this->messages[$messageToStore]['parameters'] = array();
                    }                    
                }
                
                $this->messages[$messageToStore]['count']++;
                
                if ($this->shouldNormalise() && $normalisationResult->isNormalised()) {
                    $currentParameterIndex = array();
                        
                    foreach ($normalisationResult->getNormalisedError()->getParameters() as $position => $value) {                                               
                        $currentParameterIndex[] = $value;                          
                        $parameterStore = $this->getParameterStore($currentParameterIndex, $messageToStore);
                        $parameterStore['count']++;
                        $this->setParameterStore($currentParameterIndex, $messageToStore, $parameterStore);                                            
                    }
                }
            }
            
            $this->getEntityManager()->detach($taskOutput);
        }
        
        $output->writeln('');
        $output->writeln('<info>============================================</info>');
        $output->writeln('');
        $output->writeln('Total messages analysed: ' . $messageCount);
        $output->writeln('');
        
        $this->sortMessages();
        
        $this->messages = array_slice($this->messages, 0, $this->getReportLimit());
        
        $reportData = array();
        
        foreach ($this->messages as $message => $messageStatistics) {
            if ($this->includeMessageInReport($messageStatistics) === false) {
                continue;
            }
            
            $parametersSection = '';
            
            if ($this->shouldNormalise()) {                
                $reportItem = new \stdClass();
                $reportItem->frequency = $messageStatistics['count'];
                $reportItem->normal_form = $message;
                
                if (isset($messageStatistics['parameters'])) {
                    $reportItem->parameters = $messageStatistics['parameters'];
                }
                
                $reportData[] = $reportItem;
            } else {
                $output->writeln($messageStatistics['count'] . "\t" . $message);
            }            

        }
        
        if ($this->shouldNormalise()) {
            $output->writeln($this->getFormatter()->format(json_encode($reportData)));
        }        
        
        return self::RETURN_CODE_OK;
    }
    
    private function getParameterStore($parameterIndex, $messageToStore) {
        switch (count($parameterIndex)) {
            case 1:
                if (!isset($this->messages[$messageToStore]['parameters'][$parameterIndex[0]])) {
                    $this->messages[$messageToStore]['parameters'][$parameterIndex[0]] = array(
                        'count' => 0,
                        'children' => array()
                    );
                }
                
                return $this->messages[$messageToStore]['parameters'][$parameterIndex[0]];
            
            case 2:
                if (!isset($this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]])) {
                    $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]] = array(
                        'count' => 0,
                        'children' => array()
                    );
                }
                
                return $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]];                
            
            case 3:
                if (!isset($this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]])) {
                    $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]] = array(
                        'count' => 0,
                        'children' => array()
                    );
                }
                
                return $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]];                  
            
            case 4:
                if (!isset($this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]])) {
                    $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]] = array(
                        'count' => 0,
                        'children' => array()
                    );
                }
                
                return $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]];
            
            case 5:
                if (!isset($this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]]['children'][$parameterIndex[4]])) {
                    $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]]['children'][$parameterIndex[4]] = array(
                        'count' => 0,
                        'children' => array()
                    );
                }
                
                return $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]]['children'][$parameterIndex[4]];                
        }
    }
    
    
    private function setParameterStore($parameterIndex, $messageToStore, $parameterStore) {        
        switch (count($parameterIndex)) {
            case 1:
                $this->messages[$messageToStore]['parameters'][$parameterIndex[0]] = $parameterStore;
                break;
            
            case 2:                
                $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]] = $parameterStore;                
                break;
            
            case 3:
                $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]] = $parameterStore;   
                break;
            
            case 4:
                $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]] = $parameterStore;
                break;
            
            case 5:
                $this->messages[$messageToStore]['parameters'][$parameterIndex[0]]['children'][$parameterIndex[1]]['children'][$parameterIndex[2]]['children'][$parameterIndex[3]]['children'][$parameterIndex[4]] = $parameterStore;
                break;
        }        
    }
    
    /**
     * 
     * @return boolean
     */
    private function shouldNormalise() {        
        return $this->input->getOption('normalise') == 'true';
    }    
    
    private function includeMessageInReport($messageStatistics) {
        if (is_null($this->getTypeFilter())) {
            return true;
        }
        
        if ($this->getTypeFilter() === 'N' && $messageStatistics['normalised'] === true) {
            return true;
        }
        
        if ($this->getTypeFilter() === 'R' && $messageStatistics['normalised'] === false) {
            return true;
        }
        
        return false;
    }
    
    
    private function sortMessages() {
        $frequencyIndex = array();
        
        foreach ($this->messages as $message => $messageStatistics) {
            $frequencyIndex[$message] = $messageStatistics['count'];
        }
        
        arsort($frequencyIndex);
        
        $messages = array();
        
        foreach ($frequencyIndex as $message => $count) {
            $messages[$message] = $this->messages[$message];
            
            if (isset($messages[$message]['parameters'])) {
                foreach ($messages[$message]['parameters'] as $parameterIndex => $values) {
                    arsort($values);
                    $messages[$message]['parameters'][$parameterIndex] = $values;
                }
            }
        }

        $this->messages = $messages;
    }
    
    /**
     * 
     * @return int
     */
    private function getTypeFilter() {
        return $this->input->getOption('type-filter');        
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
    
    /**
     *
     * @return \webignition\JsonPrettyPrinter\JsonPrettyPrinter
     */
    private function getFormatter() {
        if (is_null($this->formatter)) {
            $this->formatter = new \webignition\JsonPrettyPrinter\JsonPrettyPrinter();
        }
        
        return $this->formatter;
    }    
}