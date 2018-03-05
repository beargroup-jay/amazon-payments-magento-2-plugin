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

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Amazon\Core\Helper\CategoryExclusion;
use Magento\Payment\Model\Method\Logger;
use Amazon\Core\Client\ClientFactoryInterface;
use Amazon\Payment\Domain\AmazonSetOrderDetailsResponseFactory;
use Amazon\Payment\Gateway\Helper\SubjectReader;
use Amazon\Core\Exception\AmazonServiceUnavailableException;
use Amazon\Core\Helper\Data;
use Amazon\Payment\Domain\AmazonAuthorizationResponseFactory;
use Amazon\Payment\Domain\AmazonCaptureResponseFactory;

/**
 * Class AbstractClient
 * @package Amazon\Payment\Gateway\Http\Client
 */
abstract class AbstractClient implements ClientInterface
{

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ClientFactoryInterface
     */
    protected $clientFactory;

    /**
     * @var CategoryExclusion
     */
    private $categoryExclusion;

    /**
     * @var AmazonSetOrderDetailsResponseFactory
     */
    private $amazonSetOrderDetailsResponseFactory;

    /**
     * @var Data
     */
    protected $coreHelper;

    /**
     * @var AmazonAuthorizationResponseFactory
     */
    private $amazonAuthorizationResponseFactory;

    /**
     * @var AmazonCaptureResponseFactory
     */
    protected $amazonCaptureResponseFactory;


    /**
     * AbstractClient constructor.
     * @param Logger $logger
     * @param ClientFactoryInterface $clientFactory
     * @param SubjectReader $subjectReader
     * @param AmazonSetOrderDetailsResponseFactory $amazonSetOrderDetailsResponseFactory
     * @param AmazonAuthorizationResponseFactory $amazonAuthorizationResponseFactory
     * @param AmazonCaptureResponseFactory $amazonCaptureResponseFactory
     * @param CategoryExclusion $categoryExclusion
     * @param Data $coreHelper
     */
    public function __construct(
        Logger $logger,
        ClientFactoryInterface $clientFactory,
        SubjectReader $subjectReader,
        AmazonSetOrderDetailsResponseFactory $amazonSetOrderDetailsResponseFactory,
        AmazonAuthorizationResponseFactory $amazonAuthorizationResponseFactory,
        AmazonCaptureResponseFactory $amazonCaptureResponseFactory,
        CategoryExclusion $categoryExclusion,
        Data $coreHelper
    )
    {
        $this->subjectReader = $subjectReader;
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->categoryExclusion = $categoryExclusion;
        $this->amazonSetOrderDetailsResponseFactory = $amazonSetOrderDetailsResponseFactory;
        $this->amazonAuthorizationResponseFactory = $amazonAuthorizationResponseFactory;
        $this->coreHelper = $coreHelper;
        $this->amazonCaptureResponseFactory = $amazonCaptureResponseFactory;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {

        $data = $transferObject->getBody();

        $log = [
            'request' => $transferObject->getBody(),
            'client' => static::class
        ];

        $response = [];

        try {
            $response = $this->process($data);
        } catch (\Exception $e) {
            $message = __($e->getMessage() ?: "Something went wrong during Gateway request.");
            $log['error'] = $message;
            $this->logger->debug($log);
            throw new AmazonServiceUnavailableException();
        } finally {
            $log['response'] = (array) $response;
            $this->logger->debug($log);
        }

        return $response;
    }


    /**
     * @return bool
     */
    protected function checkForExcludedProducts()
    {
        if ($this->categoryExclusion->isQuoteDirty()) {
            return false;
        }
        return true;
    }

    /**
     * Sets Amazon payment order data
     * @param $storeId
     * @param $data
     * @return array
     * @throws AmazonServiceUnavailableException
     */
    protected function setOrderReferenceDetails($storeId, $data) {
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

    /**
     * Confirms that payment has been created for Amazon Pay
     * @param $storeId
     * @param $amazonOrderReferenceId
     * @return array
     * @throws AmazonServiceUnavailableException
     */
    protected function confirmOrderReference($storeId, $amazonOrderReferenceId)
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
     * Retrieves authorization data from Amazon Pay
     * @param $storeId
     * @param $data
     * @return mixed
     */
    protected function getAuthorization($storeId, $data) {
        $client = $this->clientFactory->create($storeId);

        $responseParser       = $client->authorize($data);
        $response             = $this->amazonAuthorizationResponseFactory->create(['response' => $responseParser]);
        return $response->getDetails();
    }

    /**
     * Process http request
     * @param array $data
     */
    abstract protected function process(array $data);
}