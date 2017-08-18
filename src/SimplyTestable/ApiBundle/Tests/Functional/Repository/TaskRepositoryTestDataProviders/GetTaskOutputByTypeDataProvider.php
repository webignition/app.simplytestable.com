<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

trait GetTaskOutputByTypeDataProvider
{
    /**
     * @return array
     */
    public function getTaskOutputByTypeDataProvider()
    {
        return [
            'html validation' => [
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
                            TaskTypeService::JS_STATIC_ANALYSIS_TYPE,
                            TaskTypeService::LINK_INTEGRITY_TYPE,
                        ],
                    ],
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
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                ],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'expectedOutputIndices' => [0, 1, 2, 3, 7, 11],
            ],
            'css validation' => [
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
                            TaskTypeService::JS_STATIC_ANALYSIS_TYPE,
                            TaskTypeService::LINK_INTEGRITY_TYPE,
                        ],
                    ],
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
                    [],
                    [],
                    [],
                    [],
                    [],
                    [],
                ],
                'taskTypeName' => TaskTypeService::CSS_VALIDATION_TYPE,
                'expectedOutputIndices' => [4, 8, 12],
            ],
        ];
    }
}
