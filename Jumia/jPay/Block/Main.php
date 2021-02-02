<?php
namespace Jumia\jPay\Block;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Jumia\jPay\Logger\Logger;
use Magento\Framework\App\Response\Http;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;

 
class Main extends  \Magento\Framework\View\Element\Template
{
	 protected $_objectmanager;
	 protected $checkoutSession;
	 protected $orderFactory;
	 protected $urlBuilder;
     public   $logger;
	 protected $response;
	 protected $config;
	 protected $messageManager;
	 protected $transactionBuilder;
	 protected $inbox;
	 public function __construct(Context $context,
			Session $checkoutSession,
			OrderFactory $orderFactory,
            Logger $logger,
			Http $response,
			TransactionBuilder $tb,
			 \Magento\AdminNotification\Model\Inbox $inbox,
             \Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryFactory
		) {

      
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->response = $response;
        $this->config = $context->getScopeConfig();
        $this->transactionBuilder = $tb;
         $this->logger = $logger;
         $this->inbox = $inbox;

		$this->urlBuilder = \Magento\Framework\App\ObjectManager::getInstance()
							->get('Magento\Framework\UrlInterface');
         $this->_productRepositoryFactory = $productRepositoryFactory;
		parent::__construct($context);
         $this->logger->info("Test Athar");
    }

	public function _prepareLayout()
	{
        $this->logger->info("logger test");
		$method_data = array();
		$orderId = $this->checkoutSession->getLastOrderId();
		$this->logger->info('Creating Order for orderId $orderId');
		$order = $this->orderFactory->create()->load($orderId);
		if ($order)
		{
			$billing = $order->getBillingAddress();
			# check if mobile no to be updated.
			$updateTelephone = $this->getRequest()->getParam('telephone');
			if($updateTelephone)
			{
				$billing->setTelephone($updateTelephone)->save();

			}
			$payment = $order->getPayment();

			$payment->setTransactionId("-1");
			  $payment->setAdditionalInformation(
				[\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => array("Transaction is yet to complete")]
			);
			$trn = $payment->addTransaction(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE,null,true);
			$trn->setIsClosed(0)->save();
			 $payment->addTransactionCommentsToOrder(
                $trn,
               "The transaction is yet to complete."
            );

            $payment->setParentTransactionId(null);
            $payment->save();
            $order->save();

			//var_dump($trn);exit;
			try{
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
                $x=5;
                $this->logger->info("environment: ". $x);
                $this->logger->info("environment: ". $environment);
                $this->logger->info("environment: ". $sandbox_country_list);
                $this->logger->info("environment: ". $sandbox_shop_config_key);
                $this->logger->info("environment: ". $sandbox_api_key);

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($orderId);
                // Get Order Information

                $order->getEntityId();
                $order->getIncrementId();
                $order->getState();
                $order->getStatus();
                $order->getStoreId();
                $order->getGrandTotal();
                $order->getSubtotal();
                $order->getTotalQtyOrdered();
                $order->getOrderCurrencyCode();


                // get Billing details

                $billingaddress = $order->getBillingAddress();
                $billingcity = $billingaddress->getCity();
                $billingstreet = $billingaddress->getStreet();
                $billingpostcode = $billingaddress->getPostcode();
                $billingtelephone = $billingaddress->getTelephone();
                $billingstate_code = $billingaddress->getRegionCode();

                // get shipping details

                $shippingaddress = $order->getShippingAddress();
                $shippingcity = $shippingaddress->getCity();
                $shippingstreet = $shippingaddress->getStreet();
                $shippingpostcode = $shippingaddress->getPostcode();
                $shippingtelephone = $shippingaddress->getTelephone();
                $shippingstate_code = $shippingaddress->getRegionCode();

                $grandTotal = $order->getGrandTotal();
                $subTotal = $order->getSubtotal();

                // fetch specific payment information

                $amount = $order->getPayment()->getAmountPaid();
                $paymentMethod = $order->getPayment()->getMethod();
                $info = $order->getPayment()->getAdditionalInformation('method_title');

                // Get Order Items

                $orderItems = $order->getAllItems();
                $shop_currency=$this->_storeManager->getStore()->getCurrentCurrency()->getCode();
                $basketItems=array();
                foreach ($orderItems as $item) {
                    $item->getItemId();
                    $item->getOrderId();
                    $item->getStoreId();
                    $item->getProductId();

                   // print_r($item->getProductOptions());
                    $ProductOptionsArray=$item->getProductOptions();
                    foreach($ProductOptionsArray as $ProductOptionsArrayy){
                        //print_r($ProductOptionsArrayy);
                        $this->logger->info("basketItems ".print_r($ProductOptionsArrayy,true));
                        //echo "</br>";
                        //echo "</br>";
                    }
                    $item->getSku();
                    $item->getName();
                    $item->getQtyOrdered();
                    $item->getPrice();
                    $product_id = $item->getProductId();
                    $product = $this->_productRepositoryFactory->create()
                        ->getById($item->getProductId());

                    $basketItem=[
                        "name"=> $item->getName(),
                        "imageUrl"=>$product->getData('image') ,
                        "amount"=> $item->getPrice(),
                        "quantity"=> $order->getTotalQtyOrdered(),
                        "discount"=>"",
                        "currency"=> $shop_currency
                    ];
                    array_push($basketItems,$basketItem);
                }

               // $order = $this->orderRepository->get($orderId);
                $this->logger->info("basketItems ".print_r($basketItems,true));
//                foreach ($order->getAllItems() as $item) {
//                    $itemData=var_dump($item->getData());
//                    $this->logger->info("order ".print_r($itemData,true));
//
//                }

                $data = [
                    "shopConfig" => "AAEAAADoKU7YgGwKYflQNlBOFfi-DNmu9TzbGIv3rj0uJ6z1i80Khvsu/AAEAAAgfi1Nv53Or3XXCqViItwbBHKyfjUEyDHDDPqatNxvQ4qMXzTRw/c816721cc23561541b01dca69376a5a3",
                    "basket" => array(
                        "shipping" => "0",
                        "currency" => "NGN",
                        "basketItems" => array(
                            array(
                                "name" => "Ibis Lagos Airport",
                                "imageUrl" => "https://example.com/image.jpg",
                                "amount" => "230",
                                "quantity" => "1",
                                "discount" => "",
                                "currency" => "NGN"
                            ),
                            array(
                                "name" => "Ibis Lagos Airport",
                                "imageUrl" => "https://example.com/image.jpg",
                                "amount" => "80",
                                "quantity" => "2",
                                "currency" => "NGN"
                            )
                        ),
                        "totalAmount" => "390",
                        "discount" => ""
                    ),
                    "consumerData" => array(
                        "emailAddress" => "email@example.com",
                        "mobilePhoneNumber" => "+2348021234567",
                        "country" => "NG",
                        "firstName" => "Test",
                        "lastName" => "Booking",
                        "ipAddress" => "172.16.0.1",
                        "dateOfBirth" => "",
                        "language" => "EN",
                        "name" => "Test Booking"
                    ),
                    "priceCurrency" => "NGN",
                    "description" => "Jumia Travel booking",
                    "purchaseReturnUrl" => "https://asterix.inthrs//book/payreturn/5kooig?lastname=Booking",
                    "purchaseCallbackUrl" => "https://avoranfix.inthrs/pay_panoramix_proxy",
                    "shippingAddress" => array(
                        "addressLine1" => "11 Commercial Avenue Sabo",
                        "addressLine2" => "Yaba",
                        "city" => "Lagos",
                        "district" => "Lagos",
                        "province" => "Lagos",
                        "zip" => "",
                        "country" => "NG",
                        "name" => "Test Booking",
                        "firstName" => "Test",
                        "lastName" => "Booking",
                        "mobilePhoneNumber" => "+2348021234567"
                    ),
                    "billingAddress" => array(
                        "addressLine1" => "11 Commercial Avenue Sabo",
                        "addressLine2" => "Yaba",
                        "city" => "Lagos",
                        "district" => "Lagos",
                        "province" => "Lagos",
                        "zip" => "",
                        "country" => "NG",
                        "name" => "Test Booking",
                        "firstName" => "Test",
                        "lastName" => "Booking",
                        "mobilePhoneNumber" => "+2348021234567"
                    ),
                    "additionalData" => array(),
                    "merchantReferenceId" => time().$order->getRealOrderId(),
                    "customerType" => "regular",
                    "priceAmount" => "390"
                ];

                $headers=array("apikey: X1w51boOivgwnV4QoHbWdKBlQ2MwBZBhYVpwL2PQLVLdZ3JV6Ekjg51c9Kd2FjWo","Content-type: application/json");

				$api_data['transaction_id'] = time() ."-". $order->getRealOrderId();
				$api_data['phone'] = $billing->getTelephone();
				$api_data['email'] = $billing->getEmail();
				$api_data['name'] = $billing->getFirstname() ." ". $billing->getLastname();
				$api_data['amount'] = round((int)$order->getGrandTotal(),2);
				$api_data['currency'] = "INR";
				$api_data['redirect_url'] = $this->urlBuilder->getUrl("jumia/response");
				$this->logger->info("Date sent for creating order ".print_r($api_data,true));
				$ds = DIRECTORY_SEPARATOR;
				include __DIR__ . "$ds..$ds/lib/Jumia.php";

				$api = new \Jumia($environment,$country_list,$shop_config_key,$api_key,$this->logger,$data,$headers);
				$response = $api->createOrderPayment($api_data);
				$this->logger->info("Response from Server". print_r($response,true));
				if(isset($response->order ))
				{
					$this->setAction($response->payment_options->payment_url);
					$this->checkoutSession->setPaymentRequestId($response->order->id);
				}
			}
            catch(\CurlException $e){
				// handle exception related to connection to the sever
				$this->logger->info((string)$e);
				$method_data['errors'][] = $e->getMessage();
			}
            catch(\ValidationException $e){
				// handle exceptions related to response from the server.
				$this->logger->info($e->getMessage()." with ");
				if(stristr($e->getMessage(),"Authorization"))
				{
					//$inbox->addCritical("Instamojo Authorization Error",$e->getMessage());
				}
				$this->logger->info(print_r($e->getResponse(),true)."");
				$method_data['errors'] = $e->getErrors();
			}catch(\Exception $e)
			{ // handled common exception messages which will not get caught above.
				$method_data['errors'][] = $e->getMessage();
				$this->logger->info('Error While Creating Order : ' . $e->getMessage());
			}

		}
		else
		{
			$this->logger->info('Order with ID $orderId not found. Quitting :-(');
		}



			$showPhoneBox = false;
			if(isset($method_data['errors']) and is_array($method_data['errors']))
			{
				foreach($method_data['errors'] as $error)
				{
					if(stristr($error,"phone"))
						$showPhoneBox = true;
				}

			$this->setMessages($method_data['errors']);
			}
			if($showPhoneBox)
				$this->setTelephone($api_data['phone']);
			$this->setShowPhoneBox($showPhoneBox);
	}
}
