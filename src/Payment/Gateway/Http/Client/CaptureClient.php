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


/**
 * Class Client
 * @package Amazon\Payment\Gateway\Http\Client
 */
class CaptureClient extends AbstractClient
{

    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $response = [];

        if ($this->checkForExcludedProducts()) {

            $storeId = $this->apiHelper->getStoreId();

            $authMode = $this->coreHelper->getAuthorizationMode('store', $storeId);

            $authorizeData = [
                'amazon_order_reference_id' => $data['amazon_order_reference_id'],
                'authorization_amount' => $data['amount'],
                'currency_code' => $data['currency_code'],
                'authorization_reference_id' => $data['amazon_order_reference_id'].'-A'.time(),
                'capture_now' => true
            ];

            if ($authMode == 'synchronous') {
                $authorizeData['transaction_timeout'] = 0;
            }

            $response['status'] = false;
            $response['auth_mode'] = $authMode;
            $response['amazon_order_reference_id'] = $data['amazon_order_reference_id'];

            $detailResponse = $this->setOrderReferenceDetails($storeId, $data);

            if ($detailResponse['status'] == 200) {
                $confirmResponse = $this->confirmOrderReference($storeId, $data['amazon_order_reference_id']);

                if ($confirmResponse->response['Status'] == 200) {
                    $authorizeResponse = $this->getAuthorization($storeId, $authorizeData);

                    if ($authorizeResponse) {
                        $response['capture_transaction_id'] = $authorizeResponse->getCaptureTransactionId();
                        $response['authorize_transaction_id'] = $authorizeResponse->getAuthorizeTransactionId();
                        $response['status'] = true;

                    }
                }
            }
        }

        return $response;
    }
}
