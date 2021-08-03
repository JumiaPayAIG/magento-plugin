<?php

namespace Jpay\Payments\Controller\Payment;

use Magento\Framework\App\Action\Action;

class Ipn extends Action {

    /** @var \Jpay\Payments\Logger\Logger */
    private $log;
    /**  @var \Jpay\Payments\Helper\JumiaPay */
    private $helper;

    public function __construct( \Magento\Framework\App\Action\Context $context
        , \Jpay\Payments\Logger\Logger $jpayLogger
        , \Jpay\Payments\Helper\JumiaPay $helper)
    {
        $this->log = $jpayLogger;
        $this->helper = $helper;

        parent::__construct($context);
    }

    public function execute() {
        $this->log->info(__FUNCTION__ . __(' Process the IPN response of the Jpay server'));
        $orderId = $this->getRequest()->getParam('orderId');
        $order = $this->helper->getOrder($orderId);

        /* Prepare the processing response that will be returned. */
        $_response = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);

        if (NULL === $order) {
            /* Extract the message. */
            $message = __(' Order doesn\'t exists in store');
            /* Log the error. */
            $this->log->error(__FUNCTION__ . $message);

            $_response->setHttpResponseCode(404);
            $_response->setContents('NOK');
            return $_response;
        }

        $body = urldecode($this->getRequest()->getContent());
        parse_str($body,$bodyArray);

        if (!isset($bodyArray['transactionEvents'])) {
            $this->log->error(__FUNCTION__ . ' Invalid Body on JumiaPay Callback');

            $_response->setHttpResponseCode(400);
            $_response->setContents('NOK');
            return $_response;
        }

        $JsonDecodeBody = json_decode($bodyArray['transactionEvents'], true);

        if ($order->getData('merchantReferenceId') != $JsonDecodeBody[0]['merchantReferenceId']) {
            $this->log->error(__FUNCTION__ . ' Invalid merchantReferenceId on JumiaPay Callback');

            $_response->setHttpResponseCode(400);
            $_response->setContents('NOK');
            return $_response;
        }

        /* Update the status. */
        $statusUpdate = $this->helper->handleCallback($order, $JsonDecodeBody[0]['newStatus']);

        if ($statusUpdate == false) {
            $_response->setHttpResponseCode(400);
            $_response->setContents('NOK');
            return $_response;
        }

        /* Set the OK response body. */
        $_response->setContents('OK');
        return $_response;
    }
}
