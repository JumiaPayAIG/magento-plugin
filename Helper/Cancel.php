<?php

namespace Jpay\Payments\Helper;

class Cancel extends \Magento\Framework\App\Helper\AbstractHelper {

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


    public function createCancelRequest($order) {

        $this->log->info(__FUNCTION__);

        $data = [
            "shopConfig" => $this->config->getShopKey(),
            "purchaseId"=> $order->getData('purchaseId')
        ];

        return ['json' => json_encode($data)];
    }
}
