<?php

namespace Jpay\Payments\Helper;

use Magento\Sales\Model\Order;
use Magento\Framework\App\ObjectManager;

class JumiaPay extends \Magento\Framework\App\Helper\AbstractHelper {

    /** @var \Jpay\Payments\Logger\Logger */
    private $log;

    protected $purchaseService;

    protected $paymentService;

    protected $refundService;

    protected $cancelService;

    protected $jumiaPayClient;

    protected $config;

    protected $messageManager;

    public function __construct(  \Jpay\Payments\Model\Config $config
        ,\Jpay\Payments\Logger\Logger $jpayLogger
        , \Jpay\Payments\Helper\Purchase $purchase
        , \Jpay\Payments\Helper\Client\JumiaPayClient $client
        , \Jpay\Payments\Helper\Payment $payment
        , \Jpay\Payments\Helper\Refund $refund
        , \Jpay\Payments\Helper\Cancel $cancel
        , \Magento\Framework\App\Helper\Context $context
        , \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->log = $jpayLogger;
        $this->purchaseService = $purchase;
        $this->paymentService = $payment;
        $this->refundService = $refund;
        $this->cancelService = $cancel;
        $this->jumiaPayClient = $client;
        $this->config = $config;
        $this->messageManager = $messageManager;
    }

    public function createPurchase($orderId) {
        $data = $this->purchaseService->createPurchaseRequest($orderId);
        $endpoint = $this->config->getHost() . '/merchant/create';

        $headers = $this->createHeaders();

        $checkoutUrl = $this->jumiaPayClient->makeCreatePurchaseRequest($endpoint, $headers, $data['json']);

        $this->purchaseService->setExtOrderId($orderId, $checkoutUrl['purchaseId']);

        return $checkoutUrl['checkoutUrl'];
    }

    public function createRefund($order, $amount) {
        $data = $this->refundService->createRefundRequest($order, $amount);

        $this->log->info(__FUNCTION__ . __('data: ') . $data['json']);
        $endpoint = $this->config->getHost() . '/merchant/refund';

        $headers = $this->createHeaders();

        $this->jumiaPayClient->makeRefundRequest($endpoint, $headers, $data['json']);
    }

    public function cancelPayment($order) {
        $data = $this->cancelService->createCancelRequest($order);

        $this->log->info(__FUNCTION__ . __('data: ') . $data['json']);
        $endpoint = $this->config->getHost() . '/merchant/cancel';

        $headers = $this->createHeaders();

        $this->jumiaPayClient->makeRefundRequest($endpoint, $headers, $data['json']);
    }


    public function getOrder($orderId) {
        return $this->paymentService->getOrder($orderId);
    }

    public function handleReturnUrl($purchase, $serverStatus){
        switch ($serverStatus) {
        case 'success':
            /* Set order status. */
            $this->purchaseService->setOrderState( $purchase
                , Order::STATE_PENDING_PAYMENT
                , Order::STATE_PENDING_PAYMENT
                , __(' Order #%1 as payment processing', $purchase->getIncrementId()));

            return TRUE;
            break;

        case 'failure':
            /* Set order status. */
            $this->purchaseService->setOrderState( $purchase
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

    public function handleCallback($purchase, $serverStatus){
        if ($purchase->getStatus() == Order::STATE_PENDING_PAYMENT) {
            switch ($serverStatus) {
            case "Created":
            case "Pending":
            case "Committed":
                $this->purchaseService->setOrderState( $purchase
                    , Order::STATE_PENDING_PAYMENT
                    , Order::STATE_PENDING_PAYMENT
                    , __(' Order #%1 as payment processing', $purchase->getIncrementId()));
                return TRUE;
                break;
            case "Failed":
            case "Expired":
                $this->purchaseService->setOrderState( $purchase
                    , Order::STATE_CANCELED
                    , Order::STATE_CANCELED
                    , __(' Order #%1 as payment failed', $purchase->getIncrementId()));
                return TRUE;
                break;
            case "Cancelled":
                $this->purchaseService->setOrderState( $purchase
                    , Order::STATE_CANCELED
                    , Order::STATE_CANCELED
                    , __(' Order #%1 as payment cancelled', $purchase->getIncrementId()));
                return TRUE;
                break;
            case "Completed":
                $this->purchaseService->setOrderState( $purchase
                    , Order::STATE_PROCESSING
                    , Order::STATE_PROCESSING
                    , __(' Order #%1 as payment completed', $purchase->getIncrementId()));

                $this->paymentService->addOrderTransaction($purchase->getRealOrderId(), $purchase->getData('merchantReferenceId'));
                $this->paymentService->generateInvoice($purchase, $purchase->getData('merchantReferenceId'));
                $this->paymentService->addPurchaseInvoice($purchase, $purchase->getData('merchantReferenceId'));
                return TRUE;
                break;
            default:
                $this->log->error(__FUNCTION__ . __(' [RESPONSE-ERROR]: Wrong status: ') . $serverStatus);
                return FALSE;
                break;
            }
        }


        return FALSE;
    }

    private function createHeaders() {
        return [
            'apikey: '.$this->config->getPayApiKey(),
            "Content-type: application/json"
        ];
    }
}
