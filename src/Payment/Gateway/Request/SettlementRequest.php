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
use Amazon\Core\Client\ClientFactoryInterface;
use Amazon\Payment\Domain\AmazonCaptureDetailsResponseFactory;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\App\ProductMetadata;
use Amazon\Payment\Gateway\Helper\ApiHelper;
use Amazon\Core\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Logger;

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

    private $amazonCaptureDetailsResponse;

    private $clientFactory;

    /**
     * CaptureRequest constructor.
     *
     * @param Config $config
     * @param ProductMetadata $productMetadata
     * @param ApiHelper $apiHelper
     * @param Data $coreHelper
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        AmazonCaptureDetailsResponseFactory $amazonCaptureDetailsResponse,
        ClientFactoryInterface $clientFactory,
        ProductMetaData $productMetadata,
        ApiHelper $apiHelper,
        Data $coreHelper,
        Logger $logger
    ) {
        $this->config = $config;
        $this->amazonCaptureDetailsResponse = $amazonCaptureDetailsResponse;
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
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $buildSubject['payment'];

        $orderDO = $paymentDO->getOrder();

        $order = $this->orderRepository->get($orderDO->getId());

        $quoteLink = $this->apiHelper->getQuoteLink($order->getQuoteId());

        if ($quoteLink) {



        }
/*
        if ($this->coreHelper->getCurrencyCode() !== $order->getCurrencyCode()) {
            throw new LocalizedException(__('The currency selected is not supported by Amazon Pay'));
        }

        $quote = $this->apiHelper->getQuote();

        if (!$quote->getReservedOrderId()) {
            try {
                $quote->reserveOrderId()->save();
            }
            catch(\Exception $e) {
                $this->logger->debug($e->getMessage());
            }
        }

        $amazonId = $this->apiHelper->getAmazonId();

        if ($order && $amazonId) {

            $data = [
                'amazon_order_reference_id' => $amazonId,
                'amount' => $order->getGrandTotalAmount(),
                'currency_code' => $order->getCurrencyCode(),
                'seller_order_id' => $order->getOrderIncrementId(),
                'store_name' => $quote->getStore()->getName(),
                'custom_information' =>
                    'Magento Version : ' . $this->productMetaData->getVersion() . ' ' .
                    'Plugin Version : ' . $this->coreHelper->getVersion(),
                'platform_id' => $this->config::PLATFORM_ID
            ];
        }
*/
        return $data;
    }




}

