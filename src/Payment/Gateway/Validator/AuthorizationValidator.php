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

namespace Amazon\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Amazon\Core\Exception\AmazonWebapiException;
use Amazon\Payment\Domain\AmazonAuthorizationStatus;

/**
 * Class AuthorizationValidator
 *
 * @package Amazon\Payment\Gateway\Validator
 */
class AuthorizationValidator extends AbstractValidator
{

    /**
     * Performs validation of result code
     *
     * @param  array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {

        $response = $validationSubject['response'];

        if (isset($response['sandbox'])) {

            switch ($response['sandbox']) {
                case 'Authorization:Declined:AmazonRejected':
                    $message = __('Unfortunately it is not possible to pay with Amazon Pay for this order. Please choose another payment method.');
                    $code = AmazonAuthorizationStatus::CODE_HARD_DECLINE;
                    break;
                default:
                    $message = __('There has been a problem with the selected payment method on your Amazon account. Please choose another one.');
                    $code = AmazonAuthorizationStatus::CODE_SOFT_DECLINE;
                    break;
            }

            throw new AmazonWebapiException(
                $message,
                $code,
                AmazonWebapiException::HTTP_FORBIDDEN
            );

        } else {

            if ($response['status']) {
                return $this->createResult(
                    true,
                    ['status' => $response['status']]
                );
            }

            $errorMessage = __('Payment method rejected.');
            if (isset($response['response_code'])) {
                switch ($response['response_code']) {
                    case 'BillingAgreementConstraintsExist' :
                    case '':
                        $errorMessage = __('Unable to authorize this transaction.');
                        break;
                    case 'PaymentMethodNotUpdated':
                        $errorMessage = __('Buyer did not specify valid Amazon Wallet payment method.');
                        break;
                    case 'DuplicateReferenceId' :
                    case 'DuplicateRequest' :
                    case 'PeriodicAmountExceeded':
                        $errorMessage = __('Duplicate reference or payment amount already authorized.');
                        break;
                    case 'InvalidAddress' :
                    case 'InvalidParameterValue' :
                        $errorMessage = __('Invalid values passed in request.');
                        break;
                    case 'OrderReferenceCountExceeded':
                        $errorMessage = __('You have attempted to create more than the maximum allowed limit of one Order Reference object on a Billing Agreement object that is in the Draft state.');
                        break;
                    case 'TransactionCountExceeded':
                        $errorMessage = __('Number of authorizations, captures or refunds has exceeded allowed limits.');
                        break;
                    case 'TransactionAmountExceeded':
                        $errorMessage = __('Transaction amount exceeds maximum authorization amount allowed.');
                        break;
                    case 'InvalidSandboxSimulationSpecified' :
                        $errorMessage = __('Invalid operation for sandbox environment.');
                        break;
                    case 'InvalidAddressConsentToken':
                    case 'InvalidAddressConsentToken':
                    case 'MissingAuthenticationToken':
                        $errorMessage = __('Request has an invalid signature.');
                        break;
                    case 'InvalidOrderReferenceId':
                        $errorMessage = __('Order reference ID is invalid');
                        break;
                    case 'InvalidTransactionId':
                        $errorMessage = __('Invalid transaction Identifier.');
                        break;
                    case 'InvalidAccountStatus':
                    case 'UnauthorizedAccess':
                        $errorMessage = __('Specified seller account is not authorized to execute this request.');
                        break;
                    case 'ConstraintsExist':
                        $errorMessage = __('Order Reference submitted in this request has constraints and cannot be confirmed.');
                        break;
                    case 'InvalidBillingAgreementStatus' :
                    case 'BillingAgreementNotModifiable' :
                        $errorMessage = __('Billing Agreement object cannot be modified.');
                        break;
                    case 'CaptureNotRefundable' :
                        $errorMessage = __('Capture not refundable.');
                        break;
                    case 'InvalidAuthorizationStatus':
                        $errorMessage = __('You have attempted to capture or close an authorization for an Authorization object that is in a state where a capture or close is not allowed.');
                        break;
                    case 'InvalidCancelAttempt' :
                    case 'InvalidCloseAttempt':
                        $errorMessage = __('Billing Agreement object cannot be closed or canceled.');
                        break;
                    case 'InvalidOrderReferenceStatus':
                        $errorMessage = __('You have attempted to call an operation on an Order Reference object that is in a state where that operation is not allowed.');
                        break;
                    case 'InternalServerError':
                        $errorMessage = __('There was an unknown error in the service.');
                        break;
                    case 'RequestThrottled':
                        $errorMessage = __('Request rejected because request rate is higher than allocated throttling limits.');
                        break;
                    case 'ServiceUnavailable':
                        $errorMessage = __('The service is temporarily unavailable. Please try again later.');
                        break;
                    case 'InvalidPaymentMethod':
                        $errorMessage = __('Invalid payment method.');
                        break;
                }
            }

            throw new AmazonWebapiException(
                __(
                    'There has been a problem with the selected payment method on your Amazon account. Please choose another one.'
                ),
                $response['response_code'],
                AmazonWebapiException::HTTP_FORBIDDEN
            );

            $message = __('Gateway rejected the transaction.') . ' Response code: ' . $response['response_code'] . '. Description: ' . $errorMessage;
        }

        return $this->createResult(
            false,
            [$message]
        );

    }

}
