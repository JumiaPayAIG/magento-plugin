<?php

namespace Jpay\Payments\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\PaymentException;

/**
 * This controller handles the payment back URL
 *
 * @package Jpay\Payments\Controller\Checkout
 * @author Jpay
 */
class BackUrl extends Action {

    /** @var \Jpay\Payments\Logger\Logger */
    private $log;
    /** @var \Jpay\Payments\Helper\Payment */
    private $helper;
    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Jpay\Payments\Logger\Logger $jpayLogger
     * @param \Jpay\Payments\Model\Config $config
     * @param \Jpay\Payments\Helper\Payment $helper
     */
    public function __construct( \Jpay\Payments\Logger\Logger $jpayLogger
        , \Jpay\Payments\Helper\Payment $helper
        , \Magento\Framework\Message\ManagerInterface $messageManager
        , \Magento\Framework\App\Action\Context $context)
    {
        parent::__construct($context);

        $this->log = $jpayLogger;
        $this->helper = $helper;
        $this->messageManager = $messageManager;
    }


    /**
     * Handle the back URL redirect from Jpay gateway
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() {
        $this->log->info(__FUNCTION__ . __(' Process the backUrl response of the Jpay server'));

        $orderId = $this->getRequest()->getParam('orderId');
        $paymentStatus = $this->getRequest()->getParam('paymentStatus');

        $order = $this->helper->getOrder($orderId);

        if (NULL === $order) {
            /* Extract the message. */
            $message = __(' Order doesn\'t exists in store');
            /* Log the error. */
            $this->log->error(__FUNCTION__ . $message);
            $this->messageManager->addErrorMessage(__('An error occurred in the process of payment') . ':' . $message);
            /* Redirect to fail page. */
            $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
        }


        /* Update the status. */
        $successPage = $this->helper->handleReturnUrl($order, $paymentStatus);

        if ($successPage) {
            $successPage = 'checkout/onepage/success';
            $this->_redirect($successPage, ['_secure' => TRUE]);
        } else {
            $this->messageManager->addErrorMessage(__(' The payment was cancelled'));
            $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
        }
    }
}
