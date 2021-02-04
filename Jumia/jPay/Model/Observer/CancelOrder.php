<?php

namespace Jumia\jPay\Model\Observer;

use Magento\Framework\Event\ObserverInterface;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Mail\Template\TransportBuilder;
use \Magento\Framework\Translate\Inline\StateInterface;
use Jumia\jPay\Logger\Logger;

class CancelOrder implements ObserverInterface
{
    public   $logger;
    public function __construct(
        StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        TransportBuilder $transportBuilder,
        Logger $logger,
        StateInterface $inlineTranslation,
        $environment,$country_list,$shop_config_key,$api_key,$logger,$data,$headers,$order_merchantReferenceId
    ) {
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;

        $this->logger = $logger;
        $this->data = $data;
        $this->headers = $headers;
        $this->curl = new Curl();
        $this->curl->setCacert(__DIR__."/cacert.pem");
        $this->country_list 		= $country_list;
        $this->shop_config_key	= $shop_config_key;
        $this->order_merchantReferenceId=$order_merchantReferenceId;
        $this->api_key	= $api_key;

    }
    public function execute(
        \Magento\Framework\Event\Observer $observer
    )
    {
        $this->logger->info("Test cancel");
        $data = [
            "shopConfig" => $this->shop_config_key,
            "purchaseId" => $this->order_merchantReferenceId,
        ];

    }
}