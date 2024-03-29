<?php

namespace Jpay\Payments\Controller\Payment;

use Magento\Framework\App\Action\Action;

class BackUrl extends Action {

    /** @var \Jpay\Payments\Logger\Logger */
    private $log;
    /** @var \Jpay\Payments\Helper\JumiaPay */
    private $helper;
    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    public function __construct( \Jpay\Payments\Logger\Logger $jpayLogger
        , \Jpay\Payments\Helper\JumiaPay $helper
        , \Magento\Framework\Message\ManagerInterface $messageManager
        , \Magento\Framework\App\Action\Context $context)
    {
        parent::__construct($context);

        $this->log = $jpayLogger;
        $this->helper = $helper;
        $this->messageManager = $messageManager;
    }


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
            $this->log->info(__FUNCTION__ . __('Sucess payment on returnUrl'));
            $successPage = 'checkout/onepage/success';
            $this->_redirect($successPage, ['_secure' => TRUE]);
        } else {
            $this->log->info(__FUNCTION__ . __('failed payment on returnUrl'));
            $this->messageManager->addErrorMessage(__(' The payment was cancelled'));
            $this->_redirect('checkout/onepage/failure', ['_secure' => TRUE]);
        }
    }
}
