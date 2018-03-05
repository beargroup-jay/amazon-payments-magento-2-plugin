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
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class SettlementHandler implements HandlerInterface
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var Data
     */
    private $coreHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * SettlementHandler constructor.
     * @param Logger $logger
     * @param ApiHelper $apiHelper
     * @param Data $coreHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        Logger $logger,
        ApiHelper $apiHelper,
        Data $coreHelper,
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $quoteRepository
    )
    {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->coreHelper = $coreHelper;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
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

        $payment = $paymentDO->getPayment();

        $orderDO = $paymentDO->getOrder();

        $order = $this->orderRepository->get($orderDO->getId());

        $quote = $this->quoteRepository->get($order->getQuoteId());

        $quoteLink = $this->apiHelper->getQuoteLink($quote->getId());

        // if reauthorized, treat as end of auth + capture process
        if ($response['reauthorized']) {

            // TODO check if item is async without transaction info and add to pending auth table.
            if ($response['status']) {
                $payment->setTransactionId($response['capture_transaction_id']);
                $payment->setParentTransactionId($response['authorize_transaction_id']);
                $payment->setIsTransactionClosed(true);

                $quoteLink = $this->apiHelper->getQuoteLink();
                $quoteLink->setConfirmed(true)->save();

                $message = __('Captured amount of %1 online', $order->getGrandTotal());
                $message .= ' ' . __('Transaction ID: "%1"', $quoteLink->getAmazonOrderReferenceId());

                $order->setStatus($this->coreHelper->getNewOrderStatus());
                $order->addStatusHistoryComment($message);

            }
        }
        else {
            // finish capture
            $payment->setTransactionId($response['transaction_id']);
        }
    }

}
