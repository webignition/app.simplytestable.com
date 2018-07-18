<?php

namespace App\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use App\Services\TaskTypeService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\TaskOutputFactory;

trait FindOutputByJobAndTypeDataProvider
{
    /**
     * @return array
     */
    public function findOutputByJobAndTypeDataProvider()
    {
        return [
            'no outputs' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                ],
                'taskEndTimeCollection' => [
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                ],
                'taskOutputValuesCollection' => [
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                ],
                'taskIndex' => 0,
                'limit' => false,
                'expectedRawTaskOutputs' => [],
            ],
            'match job 0' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                ],
                'taskEndTimeCollection' => [
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job0-html-validation-output0',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job0-html-validation-output1',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job0-html-validation-output2',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-html-validation-output0',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-css-validation-output0',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-html-validation-output1',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-css-validation-output1',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-html-validation-output2',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-css-validation-output2',
                    ],
                ],
                'taskIndex' => 0,
                'limit' => false,
                'expectedRawTaskOutputs' => [
                    'job0-html-validation-output2',
                    'job0-html-validation-output1',
                    'job0-html-validation-output0',
                ],
            ],
            'match job 1' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                ],
                'taskEndTimeCollection' => [
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job0-html-validation-output0',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job0-html-validation-output1',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job0-html-validation-output2',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-html-validation-output0',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-css-validation-output0',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-html-validation-output1',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-css-validation-output1',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-html-validation-output2',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-css-validation-output2',
                    ],
                ],
                'taskIndex' => 3,
                'limit' => false,
                'expectedRawTaskOutputs' => [
                    'job1-html-validation-output2',
                    'job1-html-validation-output1',
                    'job1-html-validation-output0',
                ],
            ],
            'match job 0, limit' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                ],
                'taskEndTimeCollection' => [
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                    new \DateTime(),
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job0-html-validation-output0',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job0-html-validation-output1',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job0-html-validation-output2',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-html-validation-output0',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-css-validation-output0',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-html-validation-output1',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-css-validation-output1',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-html-validation-output2',
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => 'job1-css-validation-output2',
                    ],
                ],
                'taskIndex' => 0,
                'limit' => true,
                'expectedRawTaskOutputs' => [
                    'job0-html-validation-output2',
                    'job0-html-validation-output1',
                    'job0-html-validation-output0',
                ],
            ],
        ];
    }
}
