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
use Magento\Checkout\Model\Session;
use Amazon\Payment\Api\Data\QuoteLinkInterfaceFactory;
use Amazon\Core\Client\ClientFactoryInterface;
use Amazon\Core\Exception\AmazonServiceUnavailableException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Logger;
use AmazonPay\ResponseInterface;

class TransactionIdHandler implements HandlerInterface
{
    private $_quoteLinkFactory;

    private $_checkoutSession;

    private $_clientFactory;

    private $_logger;

    public function __construct(
        QuoteLinkInterfaceFactory $quoteLinkInterfaceFactory,
        Session $session,
        ClientFactoryInterface $clientFactory,
        Logger $logger
    ) {
        $this->_quoteLinkFactory = $quoteLinkInterfaceFactory;
        $this->_checkoutSession = $session;
        $this->_clientFactory = $clientFactory;
        $this->_logger = $logger;
    }

    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();

        $quote = $this->_checkoutSession->getQuote();
        $amazonId = $this->_getAmazonId($quote->getId());
        $storeId = $this->_getStoreId();

        $valid = $this->_confirmOrderReference($amazonId, $storeId);
        $quoteLink = $this->_getQuoteLink($quote->getId());

        $quoteLink->setConfirmed(true)->save();
/*
        $payment->setTransactionId($response[self::TXN_ID]);
        $payment->setIsTransactionClosed(false);
*/
    }

    /**
     * Gets quote from current checkout session and returns store ID
     * @return int
     */
    private function _getStoreId()
    {
        $quote = $this->_checkoutSession->getQuote();
        return $quote->getStoreId();
    }

    private function _getQuoteLink($quoteId)
    {
        $quoteLink = $this->_quoteLinkFactory->create();
        $quoteLink->load($quoteId, 'quote_id');

        return $quoteLink;
    }

    /**
     * Get unique Amazon ID for order from custom table
     * @param $quoteId
     * @return mixed
     */
    private function _getAmazonId($quoteId)
    {
        $quoteLink = $this->_quoteLinkFactory->create();
        $quoteLink->load($quoteId, 'quote_id');

        return $quoteLink->getAmazonOrderReferenceId();
    }

    /**
     * @param $amazonOrderReferenceId
     * @param null $storeId
     * @throws AmazonServiceUnavailableException
     * @throws \Exception
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

    private function _validateResponse(ResponseInterface $response)
    {
        $data = $response->toArray();

        if (200 != $data['ResponseStatus']) {
            throw new AmazonServiceUnavailableException();
        }
    }


}
