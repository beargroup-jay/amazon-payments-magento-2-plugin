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
namespace Amazon\Payment\Gateway\Request;

use Amazon\Payment\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\App\ProductMetadata;
use Amazon\Payment\Gateway\Helper\ApiHelper;
use Amazon\Core\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class SettlementRequest implements BuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ProductMetadata
     */
    private $productMetaData;

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
     * SettlementRequest constructor.
     * @param Config $config
     * @param ProductMetadata $productMetadata
     * @param OrderRepositoryInterface $orderRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param ApiHelper $apiHelper
     * @param Data $coreHelper
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        ProductMetaData $productMetadata,
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $quoteRepository,
        ApiHelper $apiHelper,
        Data $coreHelper,
        Logger $logger
    ) {
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->coreHelper = $coreHelper;
        $this->productMetaData = $productMetadata;
        $this->apiHelper = $apiHelper;
        $this->logger = $logger;
    }


    /**
     * @param array $buildSubject
     * @return array
     * @throws LocalizedException
     */
    public function build(array $buildSubject)
    {
        $data = [];

        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];

        $orderDO = $paymentDO->getOrder();

        $order = $this->orderRepository->get($orderDO->getId());

        $quote = $this->quoteRepository->get($order->getQuoteId());

        $quoteLink = $this->apiHelper->getQuoteLink($quote->getId());

        if ($quoteLink) {

            $data = [
                'amazon_authorization_id' => $paymentDO->getPayment()->getParentTransactionId(),
                'capture_amount' => $buildSubject['amount'],
                'currency_code' => $order->getBaseCurrencyCode(),
                'amazon_order_reference_id' => $quoteLink->getAmazonOrderReferenceId(),
                'store_id' => $quote->getStoreId(),
                'store_name' => $quote->getStore()->getName(),
                'seller_order_id' => $order->getIncrementId(),
                'custom_information' =>
                    'Magento Version : ' . $this->productMetaData->getVersion() . ' ' .
                    'Plugin Version : ' . $this->coreHelper->getVersion(),
                'platform_id' => $this->config::PLATFORM_ID,
            ];
        }

        return $data;
    }




}

