<?php

namespace Jpay\Payments\Helper;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order\Invoice;

/**
 * Helper class for everything that has to do with payment
 *
 * @package Jpay\Payments\Helper
 * @author Jpay
 */
class Payment extends \Magento\Framework\App\Helper\AbstractHelper {
    /** @var \Jpay\Payments\Model\Config */
    private $config;
    /** @var \Jpay\Payments\Logger\Logger */
    private $log;
    /** @var \Magento\Store\Model\StoreManagerInterface: Store manager object */
    private $storeManager;
    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    private $orderRepository;
    /** @var \Magento\Sales\Model\Service\InvoiceService */
    private $invoiceService;
    /** @var \Magento\Framework\DB\TransactionFactory */
    private $transactionFactory;
    /** @var \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory */
    private $transactions;
    /** @var \Magento\Framework\App\ObjectManager */
    private $objectManager;

    protected $_productRepositoryFactory;
    protected $imageHelper;

    /************************** Inner functions START **************************/
    /**
     * Function that changes the state of an order and adds history comment.
     *
     * @param order: The purchase order to update.
     * @param state: The state to be set to the order.
     * @param status: The status to be set to the order.
     * @param comment: The comment to add to that status change.
     */
    private function setOrderState($order, $state, $status, $comment){
        /* Set the state of the order. */
        $order->setData('state', $state);
        $order->setStatus($status);

        /* Add history comment. */
        $history = $order->addStatusToHistory($status, $comment, /*isCustomerNotified*/FALSE);

        /* Save changes. */
        $order->save();
    }
    /************************** Inner functions END **************************/



    /**
     * Constructor
     *
     * @param \Jpay\Payments\Model\Config $config
     * @param \Jpay\Payments\Logger\Logger $jpayLogger
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions
     */
    public function __construct( \Jpay\Payments\Model\Config $config
        , \Jpay\Payments\Logger\Logger $jpayLogger
        , \Magento\Framework\App\Helper\Context $context
        , \Magento\Store\Model\StoreManagerInterface $storeManager
        , \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
        , \Magento\Sales\Model\Service\InvoiceService $invoiceService
        , \Magento\Framework\DB\TransactionFactory $transactionFactory
        , \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions
        , \Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryFactory
        , \Magento\Catalog\Helper\Image $imageHelper
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->log = $jpayLogger;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->transactions = $transactions;
        $this->_productRepositoryFactory = $productRepositoryFactory;
        $this->imageHelper = $imageHelper;


        $this->objectManager = ObjectManager::getInstance();
    }


    /**
     * Function that extracts an order.
     *
     * @param orderId: The ID of the order to extarct.
     *
     * @return Magento\Sales\Model\Order if found
     *         NULL if not found
     */
    public function getOrder($orderId){
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            $order = NULL;
        }

        return $order;
    }


    /**
     * Function that extracts an transaction.
     *
     * @param orderId: The ID of the order for which to extract the transaction.
     * @param txnId: The txnId of the transaction to be extracted.
     *
     * @return Magento\Sales\Model\Order\Payment\Transaction if found
     *         NULL if not found
     */
    public function getTransaction($orderId, $txnId){
        $transaction = NULL;

        foreach ($this->getTransactions($orderId) as $key => $_transaction) {
            if ($_transaction->getTxnId() == $txnId) {
                $transaction = $_transaction;
                break;
            }
        }

        return $transaction;
    }


    /**
     * Function that extracts a list of transactions for an order.
     *
     * @param orderId: The ID of the order for which to extarct
     *                  the transactions list.
     *
     * @return List of Magento\Sales\Model\Order\Payment\Transaction
     */
    public function getTransactions($orderId){
        return $this->transactions->create()->addOrderIdFilter($orderId)->getItems();
    }



    /************************** Notification START **************************/
    /**
     * Get the `jsonRequest` parameter (order parameters as JSON and base64 encoded).
     *
     * @param orderData: Array containing the order parameters.
     *
     * @return string
     */
    public function getJsonRequest(array $orderData) {
        return base64_encode(json_encode($orderData));
    }

    /**
     * Update the status of a purchase order according to the received server status.
     *
     * @param purchase: The purchase order for which to update the status.
     * @param transactionId: The unique server transaction ID of the purchase.
     * @param serverStatus: The status received from server.
     *
     * @return bool(FALSE)     - If server status in: [COMPLETE_FAIL, THREE_D_PENDING]
     *         bool(TRUE)      - If server status in: [IN_PROGRESS, COMPLETE_OK]
     */
    public function handleReturnUrl($purchase, $serverStatus){
        switch ($serverStatus) {
        case 'success':
            /* Set order status. */
            $this->setOrderState( $purchase
                , Order::STATE_PENDING_PAYMENT
                , Order::STATE_PENDING_PAYMENT
                , __(' Order #%1 as payment processing', $purchase->getIncrementId()));

            return TRUE;
            break;

        case 'failure':
            /* Set order status. */
            $this->setOrderState( $purchase
                , Order::STATE_CANCELED
                , Order::STATE_CANCELED
                , __(' Order #%1 canceled as payment failed', $purchase->getIncrementId()));

            return FALSE;
            break;

        default:
            $this->log->error(__FUNCTION__ . __(' [RESPONSE-ERROR]: Wrong status: ') . $serverStatus);
            return FALSE;
            break;
        }
    }


    /**
     * Update the status of a purchase order according to the received server status.
     *
     * @param purchase: The purchase order for which to update the status.
     * @param transactionId: The unique transaction ID of the order.
     * @param serverStatus: The status received from server.
     *
     * @return bool(FALSE)     - If server status in: [COMPLETE_FAIL, CANCEL_OK, VOID_OK, CHARGE_BACK, THREE_D_PENDING]
     *         bool(TRUE)      - If server status in: [REFUND_OK, IN_PROGRESS, COMPLETE_OK]
     */
    public function handleCallback($purchase, $serverStatus){
        if ($purchase->getStatus() == Order::STATE_PENDING_PAYMENT) {
            switch ($serverStatus) {
            case "Created":
            case "Pending":
            case "Committed":
                $this->setOrderState( $purchase
                    , Order::STATE_PENDING_PAYMENT
                    , Order::STATE_PENDING_PAYMENT
                    , __(' Order #%1 as payment processing', $purchase->getIncrementId()));
                return FALSE;
                break;
            case "Failed":
            case "Expired":
                $this->setOrderState( $purchase
                    , Order::STATE_CANCELED
                    , Order::STATE_CANCELED
                    , __(' Order #%1 as payment failed', $purchase->getIncrementId()));
                return FALSE;
                break;
            case "Cancelled":
                $this->setOrderState( $purchase
                    , Order::STATE_CANCELED
                    , Order::STATE_CANCELED
                    , __(' Order #%1 as payment cancelled', $purchase->getIncrementId()));
                return FALSE;
                break;
            case "Completed":
                $this->setOrderState( $purchase
                    , Order::STATE_PROCESSING
                    , Order::STATE_PROCESSING
                    , __(' Order #%1 as payment completed', $purchase->getIncrementId()));

                $this->addOrderTransaction($purchase->getRealOrderId(), $purchase->getExtOrderId());
                $this->addPurchaseInvoice($purchase, $purchase->getExtOrderId());
                return TRUE;
                break;
            default:
                $this->log->error(__FUNCTION__ . __(' [RESPONSE-ERROR]: Wrong status: ') . $serverStatus);
                return FALSE;
                break;
            }
        }
    }


    /**
     * Function that adds a new transaction to the order.
     *
     * @param order: The order to which to add the transaction.
     * @param serverResponse: Array containing the server decripted response.
     */
    public function addOrderTransaction($orderId, $merchantReferenceId){
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


    /**
     * Function that adds a transaction to an invoice.
     *
     * @param order: The order that has the transaction and the invoice.
     * @param transactionId: The ID of the transaction.
     */
    public function addPurchaseInvoice($order, $transactionId){
        /* Add the transaction to the invoice. */
        $invoice = $order->getInvoiceCollection()->addAttributeToSort('created_at', 'DSC')->setPage(1, 1)->getFirstItem();
        $invoice->setTransactionId($transactionId);
        $invoice->save();
    }
}
