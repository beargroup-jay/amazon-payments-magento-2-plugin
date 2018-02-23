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
use Amazon\Payment\Gateway\Helper\ApiHelper;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Amazon\Core\Helper\Data;

class CompleteCaptureHandler implements HandlerInterface
{


    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var Data
     */
    private $coreHelper;

    /**
     * CompleteCaptureHandler constructor.
     * @param ApiHelper $apiHelper
     * @param Transaction $transaction
     * @param InvoiceService $invoiceService
     * @param Data $coreHelper
     */
    public function __construct(
        ApiHelper $apiHelper,
        Transaction $transaction,
        InvoiceService $invoiceService,
        Data $coreHelper
    )
    {
        $this->apiHelper = $apiHelper;
        $this->transaction = $transaction;
        $this->invoiceService = $invoiceService;
        $this->coreHelper = $coreHelper;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {

        $amazonId = $this->apiHelper->getAmazonId();

        $order = $this->apiHelper->getOrder();

        if ($order->canInvoice()) {

            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();

            $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
            $transactionSave->save();

            $message = __('Captured amount of %1 online', $order->getGrandTotal());
            $message .= ' ' . __('Transaction ID: "%1"', $amazonId);

            $order->setStatus($this->coreHelper->getNewOrderStatus());
            $order->addStatusHistoryComment($message)->setIsCustomerNotified(true)->save();

        }
    }

}
