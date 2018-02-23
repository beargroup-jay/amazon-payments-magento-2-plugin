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

namespace Amazon\Payment\Gateway\Http\Client;

use Magento\Payment\Model\Method\Logger;
use Amazon\Core\Client\ClientFactoryInterface;
use Amazon\Payment\Domain\AmazonSetOrderDetailsResponseFactory;
use Amazon\Payment\Gateway\Helper\ApiHelper;
use Amazon\Core\Helper\CategoryExclusion;
use Amazon\Core\Exception\AmazonWebapiException;

/**
 * Class Client
 * @package Amazon\Payment\Gateway\Http\Client
 */
class Client extends AbstractClient
{

    /**
     * @var AmazonSetOrderDetailsResponseFactory
     */
    private $amazonSetOrderDetailsResponseFactory;

    /**
     * @var CategoryExclusion
     */
    private $categoryExclusion;

    /**
     * Client constructor.
     * @param Logger $logger
     * @param ClientFactoryInterface $clientFactory
     * @param ApiHelper $apiHelper
     * @param AmazonSetOrderDetailsResponseFactory $amazonSetOrderDetailsResponseFactory
     * @param CategoryExclusion $categoryExclusion
     */
    public function __construct(
        Logger $logger,
        ClientFactoryInterface $clientFactory,
        ApiHelper $apiHelper,
        AmazonSetOrderDetailsResponseFactory $amazonSetOrderDetailsResponseFactory,
        CategoryExclusion $categoryExclusion
    )
    {
        parent::__construct($logger, $clientFactory, $apiHelper);
        $this->amazonSetOrderDetailsResponseFactory = $amazonSetOrderDetailsResponseFactory;
        $this->categoryExclusion = $categoryExclusion;
    }

    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $this->checkForExcludedProducts();

        $storeId = $this->apiHelper->getStoreId();

        $responseParser = $this->clientFactory->create($storeId)->setOrderReferenceDetails($data);
        $amazonResponse = $this->amazonSetOrderDetailsResponseFactory->create([
            'response' => $responseParser
        ]);

        // Gateway expects response to be in form of array
        return [
            'status' => $responseParser->response['Status'],
            'constraints' => $amazonResponse->getConstraints(),
            'responseBody' => $responseParser->response['ResponseBody']
        ];

    }

    /**
     * @throws AmazonWebapiException
     */
    private function checkForExcludedProducts()
    {
        if ($this->categoryExclusion->isQuoteDirty()) {
            throw new AmazonWebapiException(
                __(
                    'Unfortunately it is not possible to pay with Amazon Pay for this order. Please choose another payment method.'
                ),
                AmazonAuthorizationStatus::CODE_HARD_DECLINE,
                AmazonWebapiException::HTTP_FORBIDDEN
            );
        }
    }
}
