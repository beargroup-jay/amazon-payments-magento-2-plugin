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
 * Class SettlementClient
 *
 * @package Amazon\Payment\Gateway\Http\Client
 */
class SettlementClient extends AbstractClient
{


    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $response = [];

        // check to see if authorization is still valid
        if ($this->checkAuthorizationStatus($data)) {
            $captureData = [
                'amazon_authorization_id' => $data['amazon_authorization_id'],
                'capture_amount' => $data['capture_amount'],
                'currency_code' => $data['currency_code'],
                'capture_reference_id' => $data['amazon_order_reference_id'] . '-C' . time()
            ];

            $response = $this->completeCapture($captureData, $data['store_id']);
        } else {
            // if invalid - reauthorize and capture
            $captureData = [
                'amazon_order_reference_id' => $data['amazon_order_reference_id'],
                'amount' => $data['capture_amount'],
                'currency_code' => $data['currency_code'],
                'seller_order_id' => $data['seller_order_id'],
                'store_name' => $data['store_name'],
                'custom_information' => $data['custom_information'],
                'platform_id' => $data['platform_id']
            ];
            $response = $this->authorize($data, true);
            $response['reauthorized'] = true;
        }

        return $response;
    }

    /**
     * @param $data
     * @param $storeId
     * @return null
     * @throws AmazonServiceUnavailableException
     */
    private function completeCapture($data, $storeId)
    {
        $response = [
            'status' => false
        ];

        try {
            $responseParser = $this->clientFactory->create($storeId)->capture($data);
            if ($responseParser->response['Status'] == 200) {
                $captureResponse = $this->amazonCaptureResponseFactory->create(['response' => $responseParser]);
                $capture = $captureResponse->getDetails();

                if (in_array($capture->getStatus()->getState(), self::SUCCESS_CODES)) {
                    $response = [
                        'status' => true,
                        'transaction_id' => $capture->getTransactionId(),
                        'reauthorized' => false
                    ];
                } elseif ($capture->getStatus()->getState() == 'Pending') {
                    $order = $this->subjectReader->getOrder();

                    $this->pendingCaptureFactory->create()
                        ->setCaptureId($capture->getTransactionId())
                        ->setOrderId($order->getId())
                        ->setPaymentId($order->getPayment()->getEntityId())
                        ->save();
                } else {
                    $response['response_code'] = $capture->getReasonCode();
                }
            }

        } catch (\Exception $e) {
            $log['error'] = $e->getMessage();
            $this->logger->debug($log);
            throw new AmazonServiceUnavailableException();
        }

        return $response;
    }


    /**
     * @param $data
     * @return bool
     * @throws AmazonServiceUnavailableException
     */
    private function checkAuthorizationStatus($data)
    {

        $authorizeData = [
            'amazon_authorization_id' => $data['amazon_authorization_id']
        ];

        $storeId = $data['store_id'];

        try {
            $responseParser = $this->clientFactory->create($storeId)->getAuthorizationDetails($authorizeData);
            if ($responseParser->response['Status'] != 200) {
                return false;
            }
        } catch (\Exception $e) {
            $log['error'] = $e->getMessage();
            $this->logger->debug($log);
            throw new AmazonServiceUnavailableException();
        }

        return true;
    }
}
