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

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\Method\Logger;
use Amazon\Payment\Gateway\Helper\ApiHelper;
use Amazon\Core\Helper\Data;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

class CompleteAuthHandler implements HandlerInterface
{

    /**
     * @var Data
     */
    private $coreHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * CompleteAuthHandler constructor.
     * @param Logger $logger
     * @param ApiHelper $apiHelper
     * @param Data $coreHelper
     */
    public function __construct(
        Logger $logger,
        ApiHelper $apiHelper,
        Data $coreHelper
    )
    {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->coreHelper = $coreHelper;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
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

            $payment = $paymentDO->getPayment();

            $order = $this->apiHelper->getOrder();


        // TODO check if item is async without transaction info and add to pending auth table.
        if ($response['status']) {
            $payment->setTransactionId($response['authorize_transaction_id']);
            $payment->setIsTransactionClosed(false);

            $quoteLink = $this->apiHelper->getQuoteLink();
            $quoteLink->setConfirmed(true)->save();

            $message = __('Authorized amount of %1 online', $order->getGrandTotal());
            $message .= ' ' . __('Transaction ID: "%1"', $amazonId);

            $order->setStatus($this->coreHelper->getNewOrderStatus());
            $order->addStatusHistoryComment($message);

        }

    }

}
