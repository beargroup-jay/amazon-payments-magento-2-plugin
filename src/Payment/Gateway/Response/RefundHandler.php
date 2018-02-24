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

namespace Amazon\Payment\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Amazon\Core\Exception\AmazonServiceUnavailableException;
use Amazon\Core\Helper\Data;
use Magento\Payment\Model\Method\Logger;
use Amazon\Payment\Gateway\Helper\ApiHelper;
use Magento\Framework\Notification\NotifierInterface;

class RefundHandler implements HandlerInterface
{

    private $notifierPool;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var Data
     */
    private $coreHelper;


    /**
     * TransactionIdHandler constructor.
     * @param Logger $logger
     * @param ApiHelper $apiHelper
     * @param Data $coreHelper
     */
    public function __construct(
        Logger $logger,
        ApiHelper $apiHelper,
        Data $coreHelper,
        NotifierInterface $notifierPool
    )
    {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->coreHelper = $coreHelper;
        $this->notifierPool = $notifierPool;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @throws AmazonServiceUnavailableException
     * @throws \Exception
     */
    public function handle(array $handlingSubject, array $response)
    {

        if (isset($response['status']) && $response['status'] != 200) {
            $this->notifierPool->addMajor(
                'Unable to Refund via AmazonPay',
                'The refund amount or the Amazon Order ID is incorrect.'
            );
        }
        else {
            $this->notifierPool->addNotice('AmazonPay', 'Successfully sent refund for '.$handlingSubject['amount'].' amount to AmazonPay');
        }
    }

}
