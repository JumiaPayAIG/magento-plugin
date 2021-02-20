<?php
namespace Jumia\jPay\Controller\Response;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Jumia\jPay\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Response\Http;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;
use Magento\Sales\Model\Order\Payment\Transaction;

class Index extends  \Magento\Framework\App\Action\Action
{
	protected $_objectmanager;
	protected $_checkoutSession;
	protected $_orderFactory;
	protected $urlBuilder;
	private $logger;
	protected $response;
	protected $config;
	protected $messageManager;
	protected $transactionRepository;
	protected $cart;
	protected $inbox;
    protected $orderManagement;

	public function __construct( Context $context,
			Session $checkoutSession,
			OrderFactory $orderFactory,
			Logger $logger,
			ScopeConfigInterface $scopeConfig,
			Http $response,
			TransactionBuilder $tb,

			 \Magento\Checkout\Model\Cart $cart,
			 \Magento\AdminNotification\Model\Inbox $inbox,
			 \Magento\Sales\Api\TransactionRepositoryInterface $transactionRepository,
             \Magento\Sales\Api\OrderManagementInterface $orderManagement
		) {

      
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->response = $response;
        $this->config = $scopeConfig;
        $this->transactionBuilder = $tb;
		$this->logger = $logger;					
        $this->cart = $cart;
        $this->inbox = $inbox;
        $this->orderManagement = $orderManagement;
        $this->transactionRepository = $transactionRepository;

		$this->urlBuilder = \Magento\Framework\App\ObjectManager::getInstance()
							->get('Magento\Framework\UrlInterface');

		parent::__construct($context);
    }

	public function execute()
	{
		$payment_id = $this->getRequest()->getParam('payment_id');

        $paymentStatus= isset($_GET['paymentStatus']) ? $_GET['paymentStatus'] : '';
        $orderId= isset($_GET['orderid']) ? $_GET['orderid'] : '';
        $order = $this->orderFactory->create()->load($orderId);
        if($paymentStatus=='failure'){

            $this->logger->info("paymentStatus failed");
            $this->orderManagement->cancel($orderId);
           // $this->messageManager->addCritical('error.');

            $this->_redirect($this->urlBuilder->getBaseUrl());
        }
        if($paymentStatus=='success') {
            $this->logger->info("paymentStatus success");
            $paymentLastStatus= "Payment Created";
            $order->setData('paymentLastStatus', $paymentLastStatus );

            $order->save();

            $this->_redirect($this->urlBuilder->getUrl('checkout/onepage/success/'));
        }

        if(isset($_POST)&& $paymentStatus!='failure'){
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $environment = $this->config->getValue("payment/jPay_gateway/jPayEnvironment",$storeScope);

            // live data
            $client_id = $this->config->getValue("payment/instamojo/client_id",$storeScope);
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
                "shopConfig" => $shop_config_key,
                "transactionId"=> $purchaseId,
                "transactionType"=> "Purchase"
            );
            $data= json_encode($myBody, JSON_FORCE_OBJECT);
            $endpoint ="https://api-sandbox-pay.jumia.com.ng/merchant/transaction-events";
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

            foreach($payload as $body){
                $bodyArray = (array)$body;

                if($bodyArray['newStatus']=="Created"){
                    $paymentLastStatus= "Created";
                    $order->setData('paymentLastStatus', $paymentLastStatus );
                    $order->save();
                }
                if($bodyArray['newStatus']=="Confirmed"){
                    $paymentLastStatus= "Confirmed";
                    $order->setData('paymentLastStatus', $paymentLastStatus );
                    $order->save();

                }
                if($bodyArray['newStatus']=="Committed"){
                    $paymentLastStatus= "Committed";
                    $order->setData('paymentLastStatus', $paymentLastStatus );
                    $order->save();
                }
                if($bodyArray['newStatus']=="Completed"){
                    $paymentLastStatus= "Completed";
                    $order->setState("processing")->setStatus("processing");
                    $order->setData('paymentLastStatus', $paymentLastStatus );
                    return $this->_redirect($this->urlBuilder->getUrl('jumia/invoice/')."?orderid=".$orderId);


                }
                if($bodyArray['newStatus']=="Failed"){
                    $paymentLastStatus= "Failed";
                    $this->orderManagement->cancel($orderId);
                    $order->setData('paymentLastStatus', $paymentLastStatus );
                    $order->save();
                }
                if($bodyArray['newStatus']=="cancelled"){
                    $paymentLastStatus= "cancelled";
                    $this->orderManagement->cancel($orderId);
                    $order->setData('paymentLastStatus', $paymentLastStatus );
                    $order->save();
                }
                if($bodyArray['newStatus']=="Expired"){
                    $paymentLastStatus= "Expired";
                    $this->orderManagement->cancel($orderId);
                    $order->setData('paymentLastStatus', $paymentLastStatus );
                    $order->save();
                }

            }


            curl_close($curl);


        }







    }

}
