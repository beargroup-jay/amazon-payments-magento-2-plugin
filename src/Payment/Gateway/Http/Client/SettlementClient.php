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

            $captureData = [
                'amazon_order_reference_id' => $data['amazon_order_reference_id'],
                'amount' => $data['capture_amount'],
                'currency_code' => $data['currency_code'],
                'seller_order_id' => $data['seller_order_id'],
                'store_name' => $data['store_name'],
                'custom_information' => $data['custom_information'],
                'platform_id' => $data['platform_id']
            ];
            $response = $this->authorizeAndCapture($captureData, $data['store_id']);
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
        $response = [];

        try {
            $responseParser = $this->clientFactory->create($storeId)->capture($data);
            if ($responseParser->response['Status'] == 200) {
                $captureResponse = $this->amazonCaptureResponseFactory->create(['response' => $responseParser]);
                    $capture = $captureResponse->getDetails();
                // TODO check status against Amazon\Payment\Domain\Validator\AmazonCapture validation

                $response = [
                    'status' => true,
                    'transaction_id' => $capture->getTransactionId(),
                    'reauthorized' => false
                ];
            }
            else {
                // TODO: better handle a capture operation that has already succeeded.
                throw new AmazonServiceUnavailableException();
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
     * @param $storeId
     * @return array
     * @throws AmazonServiceUnavailableException
     */
    private function authorizeAndCapture($data, $storeId)
    {
        $response = [];

            $authMode = $this->coreHelper->getAuthorizationMode('store', $storeId);

            $authorizeData = [
                'amazon_order_reference_id' => $data['amazon_order_reference_id'],
                'authorization_amount' => $data['amount'],
                'currency_code' => $data['currency_code'],
                'authorization_reference_id' => $data['amazon_order_reference_id'] . '-A' . time(),
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
                        $response['authorize_transaction_id'] = $authorizeResponse->getAuthorizeTransactionId();
                        if ($authorizeResponse->getStatus()->getState() != 'Open') {
                            $response['response_code'] = $authorizeResponse->getStatus()->getReasonCode();
                        }
                        else {
                            $response['status'] = true;
                        }

                    }
                }
                else {
                    $response['response_status'] = $confirmResponse->response['Status'];
                    try {
                        $xml = simplexml_load_string($confirmResponse->response['ResponseBody']);
                        $code = $xml->Error->Code[0];
                        if ($code) {
                            $response['response_code'] = (string) $code;
                        }

                    }
                    catch(\Exception $e) {
                        $log['error'] = $e->getMessage();
                        $this->logger->debug($log);
                    }

                }
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
