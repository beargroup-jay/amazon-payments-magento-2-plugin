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
use Amazon\Core\Exception\AmazonServiceUnavailableException;
use Amazon\Core\Helper\Data;
use Amazon\Payment\Domain\AmazonAuthorizationResponseFactory;

/**
 * Class Client
 * @package Amazon\Payment\Gateway\Http\Client
 */
class AuthorizeClient extends AbstractClient
{

    /**
     * @var AmazonSetOrderDetailsResponseFactory
     */
    private $amazonSetOrderDetailsResponseFactory;

    /**
     * @var CategoryExclusion
     */
    private $categoryExclusion;

    private $coreHelper;

    private $amazonAuthorizationResponseFactory;

    /**
     * AuthorizeClient constructor.
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
        AmazonAuthorizationResponseFactory $amazonAuthorizationResponseFactory,
        CategoryExclusion $categoryExclusion,
        Data $coreHelper
    )
    {
        $this->amazonSetOrderDetailsResponseFactory = $amazonSetOrderDetailsResponseFactory;
        $this->amazonAuthorizationResponseFactory = $amazonAuthorizationResponseFactory;
        $this->categoryExclusion = $categoryExclusion;
        $this->coreHelper = $coreHelper;
        parent::__construct($logger, $clientFactory, $apiHelper);

    }

    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $response = [];

        if ($this->checkForExcludedProducts()) {

            $storeId = $this->apiHelper->getStoreId();

            $authMode = $this->coreHelper->getAuthorizationMode('store', $storeId);

            $authorizeData = [
                'amazon_order_reference_id' => $data['amazon_order_reference_id'],
                'authorization_amount' => $data['amount'],
                'currency_code' => $data['currency_code'],
                'authorization_reference_id' => $data['amazon_order_reference_id'].'-A'.time(),
                'capture_now' => false
            ];

            if ($authMode == 'synchronous') {
                $authorizeData['transaction_timeout'] = 0;
            }

            $response['status'] = false;
            $response['auth_mode'] = $authMode;
            $response['amazon_order_reference_id'] = $data['amazon_order_reference_id'];

            $detailResponse = $this->setOrderReferenceDetails($storeId, $data);

            if ($detailResponse['status'] == 200) {
                $confirmResponse = $this->confirmOrderReference($storeId, $data['amazon_order_reference_id']);

                if ($confirmResponse->response['Status'] == 200) {
                    $authorizeResponse = $this->getAuthorization($storeId, $authorizeData);

                    if ($authorizeResponse) {
                        $response['authorize_transaction_id'] = $authorizeResponse->getAuthorizeTransactionId();
                        $response['status'] = true;

                    }
                }
            }
        }

        return $response;
    }

    private function getAuthorization($storeId, $data) {
        $client = $this->clientFactory->create($storeId);

        $responseParser       = $client->authorize($data);
        $response             = $this->amazonAuthorizationResponseFactory->create(['response' => $responseParser]);
        return $response->getDetails();
    }

    private function setOrderReferenceDetails($storeId, $data) {
        $response = [];

        try {
        $responseParser = $this->clientFactory->create($storeId)->setOrderReferenceDetails($data);
        $response = [
          'status' => $responseParser->response['Status']
        ];
        } catch (\Exception $e) {
            $log['error'] = $e->getMessage();
            $this->logger->debug($log);
            throw new AmazonServiceUnavailableException();
        }

        return $response;
    }

    private function confirmOrderReference($storeId, $amazonOrderReferenceId)
    {
        $response = [];
        try {
            $response = $this->clientFactory->create($storeId)->confirmOrderReference(
                [
                    'amazon_order_reference_id' => $amazonOrderReferenceId
                ]
            );
        } catch (\Exception $e) {
            $log['error'] = $e->getMessage();
            $this->logger->debug($log);
            throw new AmazonServiceUnavailableException();
        }

        return $response;
    }


    /**
     * @return bool
     */
    private function checkForExcludedProducts()
    {
        if ($this->categoryExclusion->isQuoteDirty()) {
            return false;
        }
        return true;
    }
}
