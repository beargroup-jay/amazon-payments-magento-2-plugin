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
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Gateway\Http\TransferInterface;
use Amazon\Core\Exception\AmazonServiceUnavailableException;
use Amazon\Core\Client\ClientFactoryInterface;
use Amazon\Payment\Gateway\Helper\ApiHelper;

/**
 * Class AbstractClient
 * @package Amazon\Payment\Gateway\Http\Client
 */
abstract class AbstractClient implements ClientInterface
{

    /**
     * @var ApiHelper
     */
    protected $_apiHelper;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var ClientFactoryInterface
     */
    protected $_clientFactory;


    /**
     * AbstractClient constructor.
     * @param Logger $logger
     * @param ClientFactoryInterface $clientFactory
     * @param ApiHelper $apiHelper
     */
    public function __construct(
        Logger $logger,
        ClientFactoryInterface $clientFactory,
        ApiHelper $apiHelper
    ) {
        $this->_logger = $logger;
        $this->_clientFactory = $clientFactory;
        $this->_apiHelper = $apiHelper;
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
            $log['error'] = $message;
            $this->_logger->debug($log);
            throw new AmazonServiceUnavailableException();
        } finally {
            $log['response'] = (array) $response;
            $this->_logger->debug($log);
        }

        return $response;
    }


    /**
     * Process http request
     * @param array $data
     */
    abstract protected function process(array $data);
}