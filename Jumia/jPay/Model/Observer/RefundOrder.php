<?php

namespace Jumia\jPay\Model\Observer;

use Magento\Sales\Model\Order;
use Magento\Framework\Event\ObserverInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Mail\Template\TransportBuilder;
use Jumia\jPay\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\OrderFactory;


class RefundOrder implements ObserverInterface
{
    private $creditmemoRepository;
    protected $orderFactory;


    public function __construct(
        CreditmemoRepositoryInterface $creditmemoRepository,
        StoreManagerInterface $storeManager,
        OrderFactory $orderFactory,
        Logger $logger,
        ScopeConfigInterface $scopeConfig

    ) {
        $this->storeManager = $storeManager;
        $this->orderFactory = $orderFactory;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->logger = $logger;
        $this->config = $scopeConfig;



    }
    public function execute(
        \Magento\Framework\Event\Observer $observer
    )
    {
        $this->logger->info("Test Refund");
        $creditmemo = $observer->getEvent()->getCreditmemo();

        $this->logger->info("Test cancel order".print_r($creditmemo->getGrandTotal(),true));
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

            $order = $this->orderFactory->create()->load($creditmemo->getOrderId());
        $purchaseId=$order->getData('purchaseId');
        $merchantReferenceId=$order->getData('merchantReferenceId');

        $shop_currency=$this->storeManager->getStore()->getCurrentCurrency()->getCode();


        $myBody=array(
            "shopConfig" => $shop_config_key,
            "purchaseId"=> $purchaseId,
            "shopConfig" => $shop_config_key,
            "refundAmount" => $creditmemo->getGrandTotal(),
            "refundCurrency" => $shop_currency,
            "description" =>  "Refund for order #".$merchantReferenceId,
            "purchaseReferenceId" => $merchantReferenceId,
            "referenceId"=> $merchantReferenceId
        );
        $data= json_encode($myBody, JSON_FORCE_OBJECT);
        $endpoint ="https://api-sandbox-pay.jumia.com.ng/merchant/refund";
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
