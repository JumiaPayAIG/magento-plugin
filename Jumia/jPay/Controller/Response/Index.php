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
		$payment_request_id = $this->getRequest()->getParam('id'); 
		$storedPaymentRequestId = $this->checkoutSession->getPaymentRequestId();
        $paymentStatus= isset($_GET['paymentStatus']) ? $_GET['paymentStatus'] : '';
        $orderId= isset($_GET['orderid']) ? $_GET['orderid'] : '';
        $order = $this->orderFactory->create()->load($orderId);
        if($paymentStatus=='failure'){

            $this->logger->info("paymentStatus failed");
            $this->orderManagement->cancel($orderId);
           // $this->messageManager->addCritical('error.');
            $this->_redirect($this->urlBuilder->getUrl('checkout/onepage/failure/'));
        }
        if($paymentStatus=='success') {
            $this->logger->info("paymentStatus success");
            $paymentLastStatus= "Payment Created";
            $order->setData('paymentLastStatus', $paymentLastStatus );
            $order->save();
            $this->_redirect($this->urlBuilder->getUrl('checkout/onepage/failure'));
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST' && $paymentStatus!='failure'){

                $body = file_get_contents('php://input');
                $DecodeBody=urldecode($body);
                parse_str($DecodeBody,$bodyArray);
                $JsonDecodeBody = json_decode($bodyArray['transactionEvents'], true);

                // save the transactionEvents for debugging purposes ( this will be removed in the production version )

                $this->logger->info("JsonDecodeBody = ".print_r($JsonDecodeBody,true));


                    if($JsonDecodeBody[0]['newStatus']=="Created"){
                        $paymentLastStatus= "Created";

                    }
                    if($JsonDecodeBody[0]['newStatus']=="Confirmed"){
                        $paymentLastStatus= "Confirmed";

                    }
                    if($JsonDecodeBody[0]['newStatus']=="Committed"){
                        $paymentLastStatus= "Committed";
                    }
                    if($JsonDecodeBody[0]['newStatus']=="Completed"){
                        $paymentLastStatus= "Completed";
                        $order->setState("complete")->setStatus("complete");
                    }
                    if($JsonDecodeBody[0]['newStatus']=="Failed"){
                        $paymentLastStatus= "Failed";
                        $this->orderManagement->cancel($orderId);
                    }
                    if($JsonDecodeBody[0]['newStatus']=="cancelled"){
                        $paymentLastStatus= "cancelled";
                        $this->orderManagement->cancel($orderId);
                    }
                    if($JsonDecodeBody[0]['newStatus']=="Expired"){
                        $paymentLastStatus= "Expired";
                        $this->orderManagement->cancel($orderId);
                    }

                     $order->setData('paymentLastStatus', $paymentLastStatus );
                     $order->save();

        }

    }

}
