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
namespace Amazon\Payment\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Amazon\Core\Client\ClientFactoryInterface;
use Amazon\Core\Exception\AmazonServiceUnavailableException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Logger;
use AmazonPay\ResponseInterface;
use Amazon\Payment\Gateway\Helper\ApiHelper;

class TransactionIdHandler implements HandlerInterface
{

    /**
     * @var ClientFactoryInterface
     */
    private $_clientFactory;

    /**
     * @var Logger
     */
    private $_logger;

    /**
     * @var ApiHelper
     */
    private $_apiHelper;

    /**
     * TransactionIdHandler constructor.
     * @param ClientFactoryInterface $clientFactory
     * @param Logger $logger
     * @param ApiHelper $apiHelper
     */
    public function __construct(
        ClientFactoryInterface $clientFactory,
        Logger $logger,
        ApiHelper $apiHelper

    ) {
        $this->_clientFactory = $clientFactory;
        $this->_logger = $logger;
        $this->_apiHelper = $apiHelper;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @throws AmazonServiceUnavailableException
     * @throws \Exception
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $amazonId = $this->_apiHelper->getAmazonId();
        $storeId = $this->_apiHelper->getStoreId();

        $valid = $this->_confirmOrderReference($amazonId, $storeId);

        if ($valid) {
            $quoteLink = $this->_apiHelper->getQuoteLink();

            $quoteLink->setConfirmed(true)->save();
        }

    }


    /**
     * @param $amazonOrderReferenceId
     * @param null $storeId
     * @return array
     * @throws AmazonServiceUnavailableException
     * @throws LocalizedException
     */
    private function _confirmOrderReference($amazonOrderReferenceId, $storeId = null)
    {
        $response = [];
        try {
            $response = $this->_clientFactory->create($storeId)->confirmOrderReference(
                [
                    'amazon_order_reference_id' => $amazonOrderReferenceId
                ]
            );

            $this->_validateResponse($response);
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Exception $e) {
            $log['error'] = $e->getMessage();
            $this->_logger->debug($log);
            throw new AmazonServiceUnavailableException();
        }

        return $response;
    }

    /**
     * @param ResponseInterface $response
     * @throws AmazonServiceUnavailableException
     */
    private function _validateResponse(ResponseInterface $response)
    {
        $data = $response->toArray();

        if (200 != $data['ResponseStatus']) {
            throw new AmazonServiceUnavailableException();
        }
    }


}
