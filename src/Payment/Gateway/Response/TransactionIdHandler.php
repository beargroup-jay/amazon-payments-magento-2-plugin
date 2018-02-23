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
    private $clientFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

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

    )
    {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
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
        $paymentDO = $handlingSubject['payment'];

        $amazonId = $this->apiHelper->getAmazonId();
        $storeId = $this->apiHelper->getStoreId();

        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();

        $message = __('Captured amount of %1 online', $order->getGrandTotalAmount());
        $message .= ' ' . __('Transaction ID: "%1"', $amazonId);

        $valid = $this->confirmOrderReference($amazonId, $storeId);

        if ($valid) {
            $payment->setTransactionId($amazonId);

            $quoteLink = $this->apiHelper->getQuoteLink();

            $quoteLink->setConfirmed(true)->save();
            // hand messaging off since OrderAdapter class doesn't have access to setting order messages
            $this->apiHelper->setOrderMessage($message);
        }

    }


    /**
     * @param $amazonOrderReferenceId
     * @param null $storeId
     * @return array
     * @throws AmazonServiceUnavailableException
     * @throws LocalizedException
     */
    private function confirmOrderReference($amazonOrderReferenceId, $storeId = null)
    {
        $response = [];
        try {
            $response = $this->clientFactory->create($storeId)->confirmOrderReference(
                [
                    'amazon_order_reference_id' => $amazonOrderReferenceId
                ]
            );

            $this->_validateResponse($response);
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Exception $e) {
            $log['error'] = $e->getMessage();
            $this->logger->debug($log);
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
