<?php

namespace Jpay\Payments\Helper;

use Magento\Sales\Model\Order;
use Magento\Framework\App\ObjectManager;

/**
 * Helper class for everything that has to do with payment
 *
 * @package Jpay\Payments\Helper
 * @author Jpay
 */
class Refund extends \Magento\Framework\App\Helper\AbstractHelper {
    /** @var \Jpay\Payments\Model\Config */
    private $config;
    /** @var \Jpay\Payments\Logger\Logger */
    private $log;

    /**
     * Constructor
     *
     * @param \Jpay\Payments\Model\Config $config
     * @param \Jpay\Payments\Logger\Logger $jpayLogger
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct( \Jpay\Payments\Model\Config $config
        , \Jpay\Payments\Logger\Logger $jpayLogger
        , \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->log = $jpayLogger;
    }


    public function createRefundRequest($order, $amount) {
            $merchantReferenceId= "R".time().$order->getRealOrderId();

            $data = [
                    "shopConfig" => $this->config->getShopKey(),
                    "refundAmount" => $amount,
                    "refundCurrency" => $order->getOrderCurrencyCode(),
                    "description" => "Refund for order #".$order->getExtOrderId(),
                    "purchaseReferenceId" => $order->getExtOrderId(),
                    "referenceId"=> $merchantReferenceId
            ];
            return ['json' => json_encode($data), 'merchantReferenceId' => $merchantReferenceId];
    }
}
