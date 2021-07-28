<?php

namespace Jpay\Payments\Model\Observer;

use Magento\Sales\Model\Order;
use Magento\Framework\Event\ObserverInterface;
use \Magento\Store\Model\StoreManagerInterface;
use Jpay\Payments\Logger\Logger;

class Cancel implements ObserverInterface
{
    protected $helper;
    protected $logger;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        Logger $logger,
        \Jpay\Payments\Helper\JumiaPay $helper
    ) {
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->helper = $helper;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    )
    {
        $order = $observer->getEvent()->getOrder();
        $this->helper->cancelPayment($order);
    }
}
