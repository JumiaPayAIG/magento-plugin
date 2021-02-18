<?php

namespace Jumia\jPay\Model\Observer;

use Magento\Sales\Model\Order;
use Magento\Framework\Event\ObserverInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Mail\Template\TransportBuilder;
use \Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\OrderFactory;
use Jumia\jPay\Logger\Logger;

class CancelOrder implements ObserverInterface
{
    protected $orderFactory;

    public function __construct(
        StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        TransportBuilder $transportBuilder,
        Logger $logger,
        OrderFactory $orderFactory,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
        $this->config = $scopeConfig;


    }
    public function execute(
        \Magento\Framework\Event\Observer $observer
    )
    {
       // $orderId = $this->checkoutSession->getLastOrderId();
        //$order = $this->orderFactory->create()->load($orderId);
        $order = $observer->getEvent()->getOrder();
        $this->logger->info("Test cancel");
        //var_dump($order);
        $this->logger->info("Test cancel order".$order->getRealOrderId());
        $purchaseId=$order->getData('purchaseId');

        $this->logger->info("purchaseId = ".$purchaseId);

        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $environment = $this->config->getValue("payment/jPay_gateway/jPayEnvironment",$storeScope);

        // live data
        $live_country_list = $this->config->getValue("payment/jPay_gateway/country_list",$storeScope);
        $live_shop_config_key = $this->config->getValue("payment/jPay_gateway/shop_config_key",$storeScope);
        $live_api_key = $this->config->getValue("payment/jPay_gateway/api_key",$storeScope);
        // sandbox data
        $sandbox_country_list = $this->config->getValue("payment/jPay_gateway/jPay_sandbox/jPayCountry",$storeScope);
        $sandbox_shop_config_key = $this->config->getValue("payment/jPay_gateway/jPay_sandbox/ShopApiKey",$storeScope);
        $sandbox_api_key = $this->config->getValue("payment/jPay_gateway/jPay_sandbox/MerchantApiKey",$storeScope);
        if($environment == "Live"){
            $country_list=$live_country_list;
            $shop_config_key=$live_shop_config_key;
            $api_key=$live_api_key;
        }
        if($environment == "Sandbox"){
            $country_list=$sandbox_country_list;
            $shop_config_key=$sandbox_shop_config_key;
            $api_key=$sandbox_api_key;
        }
        $purchaseId=$order->getData('purchaseId');
        $myBody=array(
            "shopConfig" => "AAEAAADoKU7YgGwKYflQNlBOFfi-DNmu9TzbGIv3rj0uJ6z1i80Khvsu/AAEAAAgfi1Nv53Or3XXCqViItwbBHKyfjUEyDHDDPqatNxvQ4qMXzTRw/c816721cc23561541b01dca69376a5a3",
            "purchaseId"=> $purchaseId,

        );
        $data= json_encode($myBody, JSON_FORCE_OBJECT);
        $endpoint ="https://api-sandbox-pay.jumia.com.ng/merchant/cancel";
        $headers=[
            "apiKey:".$api_key,
            "Content-type: application/json"];
        $curl = curl_init($endpoint);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //curl error SSL certificate problem, verify that the CA cert is OK

        $result		= curl_exec($curl);
        $response	= json_decode($result);

        $payload=$response->payload;
        $this->logger->info("bodyArray = ".print_r($payload,true));

        curl_close($curl);
    }
}