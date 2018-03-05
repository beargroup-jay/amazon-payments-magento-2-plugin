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

/**
 * Class RefundClient
 * @package Amazon\Payment\Gateway\Http\Client
 */
class RefundClient extends AbstractClient
{

    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $store_id = $data['store_id'];
        unset($data['store_id']);

        try {
            $client = $this->clientFactory->create($store_id);
            $responseParser = $client->refund($data);
        } catch (\Exception $e) {
            $log['error'] = $e->getMessage();
            $this->logger->debug($log);
            throw new AmazonServiceUnavailableException();
        }

        // Gateway expects response to be in form of array
        return [
            'status' => $responseParser->response['Status'],
            'constraints' => [],
            'responseBody' => $responseParser->response['ResponseBody']
        ];
    }

}
