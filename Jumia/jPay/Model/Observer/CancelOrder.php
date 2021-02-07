<?php

namespace Jumia\jPay\Model\Observer;

use Magento\Sales\Model\Order;
use Magento\Framework\Event\ObserverInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Mail\Template\TransportBuilder;
use \Magento\Framework\Translate\Inline\StateInterface;
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
        StateInterface $inlineTranslation
    ) {
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;


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
        $mymerchantReferenceId=$order->getData('merchantReferenceId');

        $this->logger->info("merchantReferenceId = ".$mymerchantReferenceId);

    }
}