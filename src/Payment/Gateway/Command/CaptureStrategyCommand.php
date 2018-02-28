<?php
/**
 * Created by PhpStorm.
 * User: Michele
 * Date: 2/27/2018
 * Time: 12:37 PM
 */

namespace Amazon\Payment\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class CaptureStrategyCommand implements CommandInterface
{

    const SALE = 'sale';

    const CAPTURE = 'settlement';

    private $commandPool;

    public function __construct(
        CommandPoolInterface $commandPool
    ) {
        $this->commandPool = $commandPool;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $commandSubject)
    {
        if ($commandSubject) {

        }
        /*
        /** @var \Magento\Payment\Gateway\Data\PaymentDataObjectInterface $paymentDO */
//        $paymentDO = $this->subjectReader->readPayment($commandSubject);

        /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $paymentInfo */
    //    $paymentInfo = $paymentDO->getPayment();
  //      ContextHelper::assertOrderPayment($paymentInfo);

      //  $command = $this->getCommand($paymentInfo);
        //$this->commandPool->get($command)->execute($commandSubject);
    }

    /**
     * Get execution command name
     * @param OrderPaymentInterface $payment
     * @return string
     */
    private function getCommand(OrderPaymentInterface $payment)
    {
        // if auth transaction is not exists execute authorize&capture command
        $existsCapture = $this->isExistsCaptureTransaction($payment);

        // do capture for authorization transaction
        if (!$existsCapture && !$this->isExpiredAuthorization($payment)) {
            return self::CAPTURE;
        }

    }

    /**
     * @param OrderPaymentInterface $payment
     * @return boolean
     */
    private function isExpiredAuthorization(OrderPaymentInterface $payment) {
        return false;
    }


    /**
     * Check if capture transaction already exists
     *
     * @param OrderPaymentInterface $payment
     * @return bool
     */
    private function isExistsCaptureTransaction(OrderPaymentInterface $payment)
    {
        $count = 0;
        /*
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('payment_id')
                    ->setValue($payment->getId())
                    ->create(),
            ]
        );

        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder
                    ->setField('txn_type')
                    ->setValue(TransactionInterface::TYPE_CAPTURE)
                    ->create(),
            ]
        );

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $count = $this->transactionRepository->getList($searchCriteria)->getTotalCount();
        */
        return (boolean) $count;
    }
}