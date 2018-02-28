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
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Sales\Api\Data\TransactionInterface;
use Amazon\Core\Helper\Data;

class CaptureStrategyCommand implements CommandInterface
{

    const SALE = 'sale';

    const CAPTURE = 'settlement';

    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var Data
     */
    private $coreHelper;

    public function __construct(
        CommandPoolInterface $commandPool,
        TransactionRepositoryInterface $transactionRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        Data $coreHelper
    ) {
        $this->commandPool = $commandPool;
        $this->transactionRepository = $transactionRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->coreHelper = $coreHelper;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $commandSubject)
    {
        if (isset($commandSubject['payment'])) {
            $paymentDO = $commandSubject['payment'];
            $paymentInfo = $paymentDO->getPayment();
            ContextHelper::assertOrderPayment($paymentInfo);

            $command = $this->getCommand($paymentInfo);
            if ($command) {
                $this->commandPool->get($command)->execute($commandSubject);
            }
        }
    }

    /**
     * Get execution command name
     * @param OrderPaymentInterface $payment
     * @return string
     */
    private function getCommand(OrderPaymentInterface $payment)
    {
        $isCaptured = $this->captureTransactionExists($payment);

        if (!$payment->getAuthorizationTransaction() && !$isCaptured) {
            return self::SALE;
        }

        if (!$isCaptured && $this->isAuthorized($payment)) {
            return self::CAPTURE;
        }

        // failed to determine action from prior tests, so use module settings
        if ($this->coreHelper->getPaymentAction() == 'authorize_capture') {
            self::SALE;
        }

        return self::CAPTURE;
    }

    /**
     * Check if auth transaction exists
     *
     * @param OrderPaymentInterface $payment
     * @return boolean
     */
    private function isAuthorized(OrderPaymentInterface $payment) {
        $filters = [];
        $this->filterBuilder->setField('transaction_id')
            ->setValue($payment->getLastTransId())
            ->create();

        $this->filterBuilder->setField('txn_type')
            ->setValue(TransactionInterface::TYPE_AUTH)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)
            ->create();

        $count = $this->transactionRepository->getList($searchCriteria)->getTotalCount();

        return (boolean) $count;

    }

    /**
     * Check if capture transaction already exists
     *
     * @param OrderPaymentInterface $payment
     * @return bool
     */
    private function captureTransactionExists(OrderPaymentInterface $payment)
    {

        $filters[] = $this->filterBuilder->setField('payment_id')
            ->setValue($payment->getId())
            ->create();

        $filters[] = $this->filterBuilder->setField('txn_type')
            ->setValue(TransactionInterface::TYPE_CAPTURE)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder->addFilters($filters)
            ->create();

        $count = $this->transactionRepository->getList($searchCriteria)->getTotalCount();

        return (boolean) $count;
    }
}