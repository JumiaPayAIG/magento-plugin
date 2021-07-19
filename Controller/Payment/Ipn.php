<?php

namespace Jpay\Payments\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\PaymentException;
use Magento\Quote\Model\QuoteIdMaskFactory;

/**
 * This controller handles the server to server notification
 *
 * @package Jpay\Payments\Controller\Checkout
 * @author Jpay
 */
class Ipn extends Action {
  /** @var \Jpay\Payments\Model\Config */
  private $config;
  /** @var \Jpay\Payments\Logger\Logger */
  private $log;
  /**  @var \Jpay\Payments\Helper\JumiaPay */
  private $helper;


  /**
   * Constructor
   *
   * @param \Magento\Framework\App\Action\Context $context
   * @param \Jpay\Payments\Logger\Logger $jpayLogger
   * @param \Jpay\Payments\Model\Config $config
   * @param \Jpay\Payments\Helper\Payment $helper
   */
  public function __construct( \Magento\Framework\App\Action\Context $context
                             , \Jpay\Payments\Logger\Logger $jpayLogger
                             , \Jpay\Payments\Model\Config $config
                             , \Jpay\Payments\Helper\JumiaPay $helper)
  {
    $this->log = $jpayLogger;
    $this->config = $config;
    $this->helper = $helper;

    parent::__construct($context);
  }

  /**
   * Function that processes the IPN (Instant Payment Notification) message of the server.
   *
   * @return \Magento\Framework\Controller\ResultInterface
   */
  public function execute() {
    $this->log->info(__FUNCTION__ . __(' Process the IPN response of the Jpay server'));
    $orderId = $this->getRequest()->getParam('orderId');
    $order = $this->helper->getOrder($orderId);

    $body = urldecode($this->getRequest()->getContent());
    parse_str($body,$bodyArray);

    $JsonDecodeBody = json_decode($bodyArray['transactionEvents'], true);

    /* Prepare the processing response that will be returned. */
    $_response = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);

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
