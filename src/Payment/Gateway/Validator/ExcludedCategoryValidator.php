<?php
/**
 * Copyright 2016 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Amazon\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Amazon\Payment\Gateway\Config\Config;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Amazon\Core\Helper\CategoryExclusion;

/**
 * Class ExcludedCategoryValidator
 * @package Amazon\Payment\Gateway\Validator
 */
class ExcludedCategoryValidator extends AbstractValidator
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CategoryExclusion
     */
    private $categoryExclusion;

    /**
     * ExcludedCategoryValidator constructor.
     * @param ResultInterfaceFactory $resultFactory
     * @param Config $config
     * @param CategoryExclusion $categoryExclusion
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Config $config,
        CategoryExclusion $categoryExclusion
    )
    {
        $this->categoryExclusion = $categoryExclusion;
        $this->config = $config;
        parent::__construct($resultFactory);
    }


    /**
     * @param array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {

        if ($this->categoryExclusion->isQuoteDirty()) {
            return $this->createResult(
                false,
                [__('Some items in the cart are not supported by Amazon Pay.')]
            );
        }

        return $this->createResult(
            true,
            ['status' => 200]
        );

    }
}