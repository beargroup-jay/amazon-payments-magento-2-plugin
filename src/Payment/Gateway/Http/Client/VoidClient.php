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

use Amazon\Core\Exception\AmazonServiceUnavailableException;
use Magento\Payment\Model\Method\Logger;
use Amazon\Core\Client\ClientFactoryInterface;
use Amazon\Payment\Gateway\Helper\ApiHelper;

/**
 * Class VoidClient
 * @package Amazon\Payment\Gateway\Http\Client
 */
class VoidClient extends AbstractClient
{

    /**
     * VoidClient constructor.
     * @param Logger $logger
     * @param ClientFactoryInterface $clientFactory
     * @param ApiHelper $apiHelper
     */
    public function __construct(
        Logger $logger,
        ClientFactoryInterface $clientFactory,
        ApiHelper $apiHelper
    )
    {
        parent::__construct($logger, $clientFactory, $apiHelper);
    }

    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $store_id = $data['store_id'];
        unset($data['store_id']);

        try {
            $client = $this->clientFactory->create($store_id);
            $responseParser = $client->cancelOrderReference($data);

            // Gateway expects response to be in form of array
            return [
                'status' => $responseParser->response['Status'],
                'constraints' => [],
                'responseBody' => $responseParser->response['ResponseBody']
            ];
        } catch (\Exception $e) {
            $log['error'] = $e->getMessage();
            $this->logger->debug($log);
            throw new AmazonServiceUnavailableException();
        }
    }

}
