<?php

namespace Jpay\Payments\Helper;

use Magento\Sales\Model\Order;
use Magento\Framework\App\ObjectManager;

class Purchase extends \Magento\Framework\App\Helper\AbstractHelper {


    private $config;

    /** @var \Jpay\Payments\Logger\Logger */
    private $log;

    /** @var \Magento\Store\Model\StoreManagerInterface: Store manager object */
    private $storeManager;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface */
    private $orderRepository;

    protected $_productRepositoryFactory;

    protected $imageHelper;

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

    public function setExtOrderId($orderId, $orderExtId) {
        $this->log->info(__FUNCTION__);

        /* Get the details of the last order. */
        $order = $this->orderRepository->get($orderId);

        /* Set order status to payment pending. */
        $order->addStatusToHistory($order->getStatus(), 'Order JumiaPay Purchase Id: '. $orderExtId);
        $order->setData('purchaseId', $orderExtId );
        $order->save();
    }

    public function createPurchaseRequest($orderId) {
        /* Get the details of the last order. */
        $order = $this->orderRepository->get($orderId);
        $this->log->info(__FUNCTION__ . __(' Create payment request for order #%1', $orderId));

        $merchantReferenceId= time().$order->getRealOrderId();

        $order->setState(Order::STATE_NEW, true);
        $order->setStatus(Order::STATE_NEW);
        $order->addStatusToHistory($order->getStatus(), 'Order JumiaPay Merchant Reference: '. $merchantReferenceId);
        /* Set order status to payment pending. */
        $order->setData('merchantReferenceId', $merchantReferenceId );
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
            $basketItem=[
                "name"=> $item->getName(),
                "amount"=> $item->getPrice(),
                "quantity"=> intval($item->getQtyOrdered()),
                "currency"=> $shop_currency
            ];
            array_push($basketItems,$basketItem);
        }

        $data = [
            "description" => substr("Payment for order " .$order->getRealOrderId(), 0, 250),
            "amount" => array(
              "value" => $grandTotal,
              "currency" => $shop_currency
            ),
            "merchant" => array(
              "referenceId" => $merchantReferenceId,
              "callbackUrl" => $this->storeManager->getStore()->getBaseUrl() . 'jpay/payment/ipn?orderId='.$order->getRealOrderId(),
              "returnUrl" => $this->storeManager->getStore()->getBaseUrl() . $this->config->getReturnUrl(). '?orderId='.$order->getRealOrderId()
            ),
            "consumer" => array(
              "emailAddress" => $billing->getEmail(),
              "ipAddress" => $remote->getRemoteAddress(),
              "country" => $billingstate_country_ID,
              "mobilePhoneNumber" => $billingtelephone,
              "language" => $store->getLocale(),
              "name" => substr($billing->getFirstname()." ".$billing->getLastname(), 0, 100),
              "firstName" => substr($billing->getFirstname() ?? '', 0, 50),
              "lastName" => substr($billing->getLastname() ?? '', 0, 50)
            ),
            "basket" => array(
                "shippingAmount" => strval($order->getShippingAmount()),
                "currency" => $shop_currency,
                "items" => $basketItems,
            ),
            "shippingAddress" => array(
                "addressLine1" => substr($shippingStreet[0] ?? '', 0, 512),
                "city" => substr($shippingcity ?? '', 0, 50),
                "district" => substr($shippingstate ?? '', 0, 50),
                "province" => substr($shippingstate ?? '', 0, 50),
                "zip" => substr($shippingpostcode ?? '', 0, 10),
                "country" => $shippingstate_country_ID,
                "name" => substr($shippingaddress->getFirstname()." ".$shippingaddress->getLastname(), 0, 100),
                "firstName" => substr($shippingaddress->getFirstname() ?? '', 0, 50),
                "lastName" => substr($shippingaddress->getLastname() ?? '', 0, 50),
                "mobilePhoneNumber" => $shippingtelephone
            ),
            "billingAddress" => array(
                "addressLine1" => substr($billingStreet[0] ?? '', 0, 512),
                "city" => substr($billingcity ?? '', 0, 50),
                "district" => substr($billingstate ?? '', 0, 50),
                "province" => substr($billingstate ?? '', 0, 50),
                "zip" => substr($billingpostcode ?? '', 0, 10),
                "country" => $billingstate_country_ID,
                "name" => substr($billing->getFirstname()." ".$billing->getLastname(), 0, 100),
                "firstName" => substr($billing->getFirstname() ?? '', 0, 50),
                "lastName" => substr($billing->getLastname() ?? '', 0, 50),
                "mobilePhoneNumber" => $billingtelephone
            ),
        ];

        return [
          'json' => json_encode(
            array_filter( $data, function( $v ) { return !( is_null( $v) or '' === $v ); } )
          ),
          'merchantReferenceId' => $merchantReferenceId
        ];
    }

    public function setOrderStateByID($orderId, $state, $status){
        $order = $this->orderRepository->get($orderId);

        $this->log->info(__FUNCTION__);

        $order->setState($state, true);
        $order->setStatus($status);

        /* Save changes. */
        $order->save();
    }

    public function setOrderState($order, $state, $status, $comment){
        $this->log->info(__FUNCTION__);
        /* Set the state of the order. */
        $order->setData('state', $state);
        $order->setStatus($status);

        /* Add history comment. */
        $history = $order->addStatusToHistory($status, $comment, /*isCustomerNotified*/FALSE);

        /* Save changes. */
        $order->save();
    }
}
