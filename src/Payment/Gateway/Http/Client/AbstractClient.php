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

use Magento\Payment\Gateway\Http\ClientInterface;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Http\TransferInterface;
use Amazon\Core\Exception\AmazonServiceUnavailableException;
use Amazon\Core\Client\ClientFactoryInterface;

/**
 * Class AbstractClient
 * @package Amazon\Payment\Gateway\Http\Client
 */
abstract class AbstractClient implements ClientInterface
{

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var ClientFactoryInterface
     */
    protected $_clientFactory;


    /**
     * @var Session
     */
    protected $_checkoutSession;


    /**
     * AbstractClient constructor.
     *
     * @param Logger $logger
     * @param ClientFactoryInterface $clientFactory
     * @param Session $checkoutSession
     */
    public function __construct(
        LoggerInterface $logger,
        ClientFactoryInterface $clientFactory,
        Session $checkoutSession
    ) {
        $this->_logger = $logger;
        $this->_clientFactory = $clientFactory;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {

        $data = $transferObject->getBody();

        $log = [
            'request' => $transferObject->getBody(),
            'client' => static::class
        ];

        $response = [];

        try {
            $response = $this->process($data);
        } catch (\Exception $e) {
            $message = __($e->getMessage() ?: "Something went wrong during Gateway request.");
            $this->_logger->critical($message);
            throw new AmazonServiceUnavailableException();
        } finally {
            $log['response'] = (array) $response;
            $this->_logger->debug($log);
        }

        return $response;
    }


    /**
     * Gets quote from current checkout session and returns store ID
     * @return int
     */
    protected function _getStoreId()
    {
        $quote = $this->_checkoutSession->getQuote();
        return $quote->getStoreId();
    }

    /**
     * Process http request
     * @param array $data
     */
    abstract protected function process(array $data);
}