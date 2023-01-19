<?php

namespace Jpay\Payments\Helper;

class Refund extends \Magento\Framework\App\Helper\AbstractHelper {

    /** @var \Jpay\Payments\Model\Config */
    private $config;
    /** @var \Jpay\Payments\Logger\Logger */
    private $log;

    public function __construct( \Jpay\Payments\Model\Config $config
        , \Jpay\Payments\Logger\Logger $jpayLogger
        , \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->log = $jpayLogger;
    }


    public function createRefundRequest($order, $amount) {

        $this->log->info(__FUNCTION__);

        $merchantReferenceId= "R".time().$order->getRealOrderId();

        $data = [
            "shopConfig" => $this->config->getShopKey(),
            "refundAmount" => $amount,
            "refundCurrency" => $order->getOrderCurrencyCode(),
            "description" => "Refund for order #".$order->getData('purchaseId'),
            "purchaseReferenceId" => $order->getData('merchantReferenceId'),
            "purchaseId" => $order->getData('purchaseId'),
            "referenceId"=> $merchantReferenceId
        ];
        return ['json' => json_encode($data), 'merchantReferenceId' => $merchantReferenceId];
    }
}
