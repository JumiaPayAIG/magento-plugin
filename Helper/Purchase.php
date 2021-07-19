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
class Purchase extends \Magento\Framework\App\Helper\AbstractHelper {
    /** @var \Jpay\Payments\Model\Config */
    private $config;
    /** @var \Jpay\Payments\Logger\Logger */
    private $log;
    /** @var \Magento\Store\Model\StoreManagerInterface: Store manager object */
    private $storeManager;
    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    private $orderRepository;

    protected $_productRepositoryFactory;
    protected $imageHelper;

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
        , \Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryFactory
        , \Magento\Catalog\Helper\Image $imageHelper
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->log = $jpayLogger;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->_productRepositoryFactory = $productRepositoryFactory;
        $this->imageHelper = $imageHelper;
    }


    /**
     * Prepares the request data to be sent to the Jpay gateway
     *
     * @param orderId - The ID of the ordered to be payed.
     * @param isGuest - Flag indicating if the order comes from an authenticated customer or a guest.
     *
     * @return array([key => value,]) - Representing the JSON to be sent to the payment gateway.
     *         bool(FALSE)            - Otherwise
     */
    public function createPurchaseRequest($orderId) {
        /* Get the details of the last order. */
        $order = $this->orderRepository->get($orderId);
        $this->log->info(__FUNCTION__ . __(' Create payment request for order #%1', $orderId));

        $merchantReferenceId= time().$order->getRealOrderId();

        /* Set order status to payment pending. */
        $order->setState(Order::STATE_PENDING_PAYMENT, true);
        $order->setStatus(Order::STATE_PENDING_PAYMENT);
        $order->addStatusToHistory($order->getStatus(), 'Order External ID: '. $merchantReferenceId);
        $order->setExtOrderId($merchantReferenceId);
        $order->save();

        $billing = $order->getBillingAddress();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $store  = $objectManager->get('Magento\Framework\Locale\Resolver');
        $remote = $objectManager->get('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');

        // get Billing details
        $billingaddress = $order->getBillingAddress();
        $billingcity = $billingaddress->getCity();
        $billingStreet  = $billingaddress->getStreet();
        $billingstate  = $billingaddress->getState();
        $billingpostcode = $billingaddress->getPostcode();
        $billingtelephone = $billingaddress->getTelephone();
        $billingstate_country_ID = $billingaddress->getCountryId();

        // get shipping details
        $shippingaddress = $order->getShippingAddress();
        $shippingcity = $shippingaddress->getCity();
        $shippingstate = $shippingaddress->getState();
        $shippingStreet = $shippingaddress->getStreet();
        $shippingpostcode = $shippingaddress->getPostcode();
        $shippingtelephone = $shippingaddress->getTelephone();
        $shippingstate_country_ID = $shippingaddress->getCountryId();

        $grandTotal = $order->getGrandTotal();

        // Get Order Items
        $orderItems = $order->getAllVisibleItems();
        $shop_currency=$order->getOrderCurrencyCode();
        $basketItems=array();
        foreach ($orderItems as $item) {
            $product = $this->_productRepositoryFactory->create()
                                                       ->getById($item->getProductId());

            $image_url = $this->imageHelper->init($product, 'product_base_image')->getUrl();

            $basketItem=[
                "name"=> $item->getName(),
                "imageUrl"=>$image_url,
                "amount"=> $item->getPrice(),
                "quantity"=> intval($item->getQtyOrdered()),
                "discount"=>"",
                "currency"=> $shop_currency
            ];
            array_push($basketItems,$basketItem);
        }

        $data = [
            "shopConfig" => $this->config->getShopKey(),
            "basket" => array(
                "shipping" =>strval($order->getShippingAmount()),
                "currency" => $shop_currency,
                "basketItems" => $basketItems,
                "totalAmount" => $grandTotal,
                "discount" => ""
            ),
            "consumerData" => array(
                "emailAddress" => $billing->getEmail(),
                "mobilePhoneNumber" => $billingtelephone,
                "country" => $billingstate_country_ID,
                "firstName" => $billing->getFirstname(),
                "lastName" => $billing->getLastname(),
                "ipAddress" => $remote->getRemoteAddress(),
                "dateOfBirth" => "",
                "language" => $store->getLocale(),
                "name" => $billing->getFirstname()." ".$billing->getLastname(),
            ),
            "priceCurrency" => $shop_currency,
            "purchaseReturnUrl" => $this->storeManager->getStore()->getBaseUrl() . $this->config->getReturnUrl(). '?orderId='.$order->getRealOrderId(),
            "purchaseCallbackUrl" => $this->storeManager->getStore()->getBaseUrl() . 'jpay/payment/ipn?orderId='.$order->getRealOrderId(),
            //'backUrl' => $this->storeManager->getStore()->getBaseUrl() . $this->config->getBackUrl(),
            "shippingAddress" => array(
                "addressLine1" => $shippingStreet[0],
                "addressLine2" => "Yaba",
                "city" => $shippingcity,
                "district" => $shippingstate,
                "province" => $shippingstate,
                "zip" => $shippingpostcode,
                "country" => $shippingstate_country_ID,
                "name" => $shippingaddress->getFirstname()." ".$shippingaddress->getLastname(),
                "firstName" => $shippingaddress->getFirstname(),
                "lastName" => $shippingaddress->getLastname(),
                "mobilePhoneNumber" => $shippingtelephone
            ),
            "billingAddress" => array(
                "addressLine1" => $billingStreet[0],
                "addressLine2" => "Yaba",
                "city" => $billingcity,
                "district" => $billingstate,
                "province" => $billingstate,
                "zip" => $billingpostcode,
                "country" => $billingstate_country_ID,
                "name" => $billing->getFirstname()." ".$billing->getLastname(),
                "firstName" => $billing->getFirstname(),
                "lastName" => $billing->getLastname(),
                "mobilePhoneNumber" => $billingtelephone
            ),
            "additionalData" => array(),
            "merchantReferenceId" => $merchantReferenceId,
            "customerType" => "regular",
            "priceAmount" => $grandTotal
        ];

        return ['json' => json_encode($data), 'merchantReferenceId' => $merchantReferenceId];
    }
}