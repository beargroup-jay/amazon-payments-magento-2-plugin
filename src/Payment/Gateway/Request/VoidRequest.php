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

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\App\ProductMetadata;
use Amazon\Payment\Gateway\Helper\ApiHelper;
use Amazon\Core\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;

class VoidRequest implements BuilderInterface
{

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
     * VoidRequest constructor.
     * @param ProductMetadata $productMetadata
     * @param ApiHelper $apiHelper
     * @param Data $coreHelper
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        ProductMetaData $productMetadata,
        ApiHelper $apiHelper,
        Data $coreHelper,
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->coreHelper = $coreHelper;
        $this->productMetaData = $productMetadata;
        $this->apiHelper = $apiHelper;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
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

        if ($order) {
            $quoteLink = $this->apiHelper->getQuoteLink($order->getQuoteId());

            if ($quoteLink) {
                $data = [
                    'amazon_order_reference_id' => $quoteLink->getAmazonOrderReferenceId()
                ];
            }
        }

        return $data;
    }
}
