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


class GetOutputIdsForErrorCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_INVALID_TASK_TYPE = 2;    
    const RETURN_CODE_INVALID_FRAGMENTS = 3;    
    
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
            ->setName('simplytestable:reporting:get-output-ids-for-error')
            ->setDescription('Get list of task output ids matching certain error messages')
            ->addOption('task-type', null, InputOption::VALUE_REQUIRED, 'Name of task type for which to generate report')
            ->addOption('task-output-limit', null, InputOption::VALUE_OPTIONAL, 'Limit the number of task outputs processed')
            ->addOption('task-output-offset', null, InputOption::VALUE_OPTIONAL, 'Offset for task output list')
            ->addOption('fragments', null, InputOption::VALUE_OPTIONAL, 'Fragments of errors to match against')
            ->setHelp(<<<EOF
Get list of task output ids matching certain error messages
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
        
        $output->write('<info>Fragments to match: ... </info>'); 
        $fragments = $this->getFragments();
        
        if (count($fragments) === 0) {
            $output->write('none specified, stopping'); 
            return self::RETURN_CODE_INVALID_FRAGMENTS;
        }
        
        $output->writeln(implode(', ', $fragments));                
        
        $limit = $this->getTaskOutputLimit();
        $output->write('<info>Requested limit: ');

        if (is_null($limit)) {
            $output->writeln('NONE');
        } else {
            $output->writeln($limit);
        }        
        
        $offset = $this->getTaskOutputOffset();
        $output->write('<info>Requested offset: ');
        
        if (is_null($offset)) {
            $output->writeln('NONE');
        } else {
            $output->writeln($offset);
        }
        
        $output->writeln('');
        
        $output->write('Finding task output for ['.$this->taskType->getName().'] tasks ... ');
        
        $taskOutputIds = $this->getTaskOutputRepository()->findIdsByTaskType($this->taskType, $limit, $offset);
        $taskOutputCount = count($taskOutputIds);
        
        $output->writeln('['.$taskOutputCount.'] task outputs found');
        
        $processedTaskOutputCount = 0;
        
        $normaliser = new HtmlValidationErrorNormaliser();
        
        $messageCount = 0;
        $outputIds = array();
        
        foreach ($taskOutputIds as $taskOutputId) {            
            $processedTaskOutputCount++;
            $output->write('.');
            
            $taskOutput = $this->getTaskOutputRepository()->find($taskOutputId);
            
            $messages = $this->getMessagesForTaskOutput($taskOutput);
            
            foreach ($messages as $message) {
                if ($this->isFragmentMatch($message, $fragments)) {
                    $outputIds[] = $taskOutputId;
                }
            }
            
            $this->getManager()->detach($taskOutput);
        }
        
        $output->writeln('');
        $output->writeln('<info>============================================</info>');
        $output->writeln('');
        $output->writeln('Outputs found: ' . count($outputIds));
        $output->writeln('');
        $output->writeln(implode(',',$outputIds));
        $output->writeln('');  
        
        return self::RETURN_CODE_OK;
    }
    
    
    /**
     * 
     * @param string $message
     * @param array $fragments
     * @return boolean
     */
    private function isFragmentMatch($message, $fragments) {        
        $isMatch = true;
        $message = strtolower($message);
        
        foreach ($fragments as $fragment) {            
            $fragment = strtolower(trim($fragment));
            if (!substr_count($message, $fragment)) {
                $isMatch = false;
            }
        }
        
        return $isMatch;
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
     * @return array
     */
    private function getFragments() {
        $fragmentsString = $this->input->getOption('fragments');        
        if (is_null($fragmentsString)) {
            return array();
        }
        
        return explode(',', $fragmentsString);
    }
        
    
    /**
     * 
     * @return int
     */
    private function getTaskOutputOffset() {
        $offset = (int)$this->input->getOption('task-output-offset');        
        return ($offset > 0) ? $offset : null;
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
    private function getManager() {
        if (is_null($this->entityManager)) {
            $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        }
        
        return  $this->entityManager;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Repository\TaskOutputRepository
     */
    private function getTaskOutputRepository() {
        if (is_null($this->taskOutputRepository)) {
            $this->taskOutputRepository = $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output');
        }
        
        return $this->taskOutputRepository;
    }  
}