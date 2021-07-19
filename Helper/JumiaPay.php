<?php

namespace Jpay\Payments\Helper;

use Magento\Sales\Model\Order;
use Magento\Framework\App\ObjectManager;

/**
 * Helper class for everything that has to do with payment
 *
 * @package Jpay\Payments\Helper
 * @author Jpay
 */
class JumiaPay extends \Magento\Framework\App\Helper\AbstractHelper {

    /** @var \Jpay\Payments\Logger\Logger */
    private $log;

    protected $purchaseService;

    protected $paymentService;

    protected $config;

    protected $messageManager;

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
    public function __construct(  \Jpay\Payments\Model\Config $config
        ,\Jpay\Payments\Logger\Logger $jpayLogger
        , \Jpay\Payments\Helper\Purchase $purchase
        , \Jpay\Payments\Helper\Payment $payment
        , \Magento\Framework\App\Helper\Context $context
        , \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->log = $jpayLogger;
        $this->purchaseService = $purchase;
        $this->paymentService = $payment;
        $this->config = $config;
        $this->messageManager = $messageManager;
    }

    public function createPurchase($orderId) {
        $data = $this->purchaseService->createPurchaseRequest($orderId);
        $endpoint = $this->config->getHost() . '/merchant/create';

        $headers = $this->createHeaders();

        $checkoutUrl = $this->makeCreatePurchaseRequest($endpoint, $headers, $data['json']);

        return $checkoutUrl;
    }

    public function handleCallback($orderId, $callbackRequest) {
        $order = $this->helper->getOrder($orderId);

        $statusUpdate = $this->paymentService->updateStatus_purchase_IPN($order, $decrypted['transactionId'], $decrypted['transactionStatus']);
    }

    private function createHeaders() {
        return [
            'apikey: '.$this->config->getPayApiKey(),
            "Content-type: application/json"
        ];
    }

    private function makeCreatePurchaseRequest($endpoint, $headers, $body) {

        $curl = curl_init($endpoint);

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //curl error SSL certificate problem, verify that the CA cert is OK

        $result		= curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response	= json_decode($result, true);


        if ($httpcode != 200) {
            if (isset($response['payload'][0]['description'])) {
                $this->messageManager->addErrorMessage($response['payload'][0]['description']);
                throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase($response['payload'][0]['description']));
            }

            $this->messageManager->addErrorMessage("Error Conecting to JumiaPay");
            throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase("Error Conecting to JumiaPay"));
        }

        return $response['payload']['checkoutUrl'];
    }
}
