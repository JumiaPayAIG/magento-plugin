<?php

namespace Jpay\Payments\Helper;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Invoice;

class Payment extends \Magento\Framework\App\Helper\AbstractHelper {

    /** @var \Jpay\Payments\Logger\Logger */
    private $log;
    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    private $orderRepository;
    /** @var \Magento\Sales\Model\Service\InvoiceService */
    private $invoiceService;
    /** @var \Magento\Framework\DB\TransactionFactory */
    private $transactionFactory;
    /** @var \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory */
    private $transactions;

    public function __construct( \Jpay\Payments\Logger\Logger $jpayLogger
        , \Magento\Framework\App\Helper\Context $context
        , \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
        , \Magento\Sales\Model\Service\InvoiceService $invoiceService
        , \Magento\Framework\DB\TransactionFactory $transactionFactory
        , \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions
    ) {
        parent::__construct($context);
        $this->log = $jpayLogger;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->transactions = $transactions;
    }


    public function getOrder($orderId){
        $this->log->info(__FUNCTION__);
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            $order = NULL;
        }

        return $order;
    }

    public function getTransaction($orderId, $txnId){
        $this->log->info(__FUNCTION__);
        $transaction = NULL;

        foreach ($this->getTransactions($orderId) as $key => $_transaction) {
            if ($_transaction->getTxnId() == $txnId) {
                $transaction = $_transaction;
                break;
            }
        }

        return $transaction;
    }

    public function getTransactions($orderId){
        $this->log->info(__FUNCTION__);
        return $this->transactions->create()->addOrderIdFilter($orderId)->getItems();
    }

    public function addOrderTransaction($orderId, $merchantReferenceId){
        $this->log->info(__FUNCTION__);

        $order = $this->orderRepository->get($orderId);

        /* Save the payment transaction. */
        $payment = $order->getPayment();
        $payment->setTransactionId($merchantReferenceId);
        $payment->setLastTransId($merchantReferenceId);
        $payment->setParentTransactionId(NULL);

        $transaction = $payment->addTransaction(Transaction::TYPE_CAPTURE, null, TRUE, 'OK');
        // $transaction->setIsClosed(FALSE);
        $transaction->setCreatedAt(date("D M d, Y G:i"));
        $transaction->save();
        $payment->save();

        $order->setExtOrderId($merchantReferenceId);
        $order->save();
    }


    public function addPurchaseInvoice($order, $transactionId){
        $this->log->info(__FUNCTION__);

        /* Add the transaction to the invoice. */
        $invoice = $order->getInvoiceCollection()->addAttributeToSort('created_at', 'DSC')->setPage(1, 1)->getFirstItem();
        $invoice->setTransactionId($transactionId);
        $invoice->save();
    }

    public function generateInvoice($order, $transactionId){
        $this->log->info(__FUNCTION__ . __(': START'));

        $invoice = $this->invoiceService->prepareInvoice($order);
        if (!$invoice || !$invoice->getTotalQty()) {
            $this->log->info(__FUNCTION__ . __(': null qty'));
            return FALSE;
        }

        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->register();
        $invoice->getOrder()->setCustomerNoteNotify(FALSE);
        $invoice->getOrder()->setIsInProcess(TRUE);
        $invoice->setTransactionId($transactionId);
        $invoice->save();
        $order->addStatusHistoryComment('Automatically INVOICED', FALSE);
        $transactionSave = $this->transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
        $transactionSave->save();

        return TRUE;
    }
}
