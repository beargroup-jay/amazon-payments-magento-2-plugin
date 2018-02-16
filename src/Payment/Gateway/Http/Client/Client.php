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

use Psr\Log\LoggerInterface;
use Amazon\Core\Client\ClientFactoryInterface;
use Amazon\Payment\Domain\AmazonSetOrderDetailsResponseFactory;
use Magento\Checkout\Model\Session;

/**
 * Class Client
 * @package Amazon\Payment\Gateway\Http\Client
 */
class Client extends AbstractClient
{

    /**
     * @var AmazonSetOrderDetailsResponseFactory
     */
    private $_amazonSetOrderDetailsResponseFactory;

    /**
     * Client constructor.
     * @param LoggerInterface $logger
     * @param ClientFactoryInterface $clientFactory
     * @param Session $checkoutSession
     * @param AmazonSetOrderDetailsResponseFactory $amazonSetOrderDetailsResponseFactory
     */
    public function __construct(
        LoggerInterface $logger,
        ClientFactoryInterface $clientFactory,
        Session $checkoutSession,
        AmazonSetOrderDetailsResponseFactory $amazonSetOrderDetailsResponseFactory
    )
    {
        parent::__construct($logger, $clientFactory, $checkoutSession);
        $this->_amazonSetOrderDetailsResponseFactory = $amazonSetOrderDetailsResponseFactory;
    }

    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $storeId = $this->_getStoreId();

        $responseParser = $this->_clientFactory->create($storeId)->setOrderReferenceDetails($data);
        $response = $this->_amazonSetOrderDetailsResponseFactory->create([
            'response' => $responseParser
        ]);

        return $response;
    }


}
