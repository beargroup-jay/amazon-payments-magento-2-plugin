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
use Amazon\Core\Helper\Data;
use Magento\Payment\Model\Method\Logger;
use Amazon\Payment\Gateway\Helper\ApiHelper;
use Magento\Framework\Message\ManagerInterface;

class SettlementHandler implements HandlerInterface
{

    /**
     * @var ManagerInterface
     */
    private $messageManager;

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
     * RefundHandler constructor.
     * @param Logger $logger
     * @param ApiHelper $apiHelper
     * @param Data $coreHelper
     * @param $messageManager
     */
    public function __construct(
        Logger $logger,
        ApiHelper $apiHelper,
        Data $coreHelper,
        ManagerInterface $messageManager
    )
    {
        $this->logger = $logger;
        $this->apiHelper = $apiHelper;
        $this->coreHelper = $coreHelper;
        $this->messageManager = $messageManager;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (isset($response['status']) && $response['status'] != 200) {
            $this->messageManager->addErrorMessage(
                'Amazon Order ID is incorrect.'
            );
        }
        else {
            // TODO: finish creating invoice/authorize payment
            //$this->messageManager->addSuccessMessage('Successfully sent refund for '.$handlingSubject['amount'].' amount to AmazonPay');
        }
    }

}
