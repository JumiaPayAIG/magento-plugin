<?php

namespace Jpay\Payments\Plugin;

/**
 * Jpay payment method  CSRF validator
 *
 * @category    Jpay\Payments\Plugin
 * @package     Jpay_Payments
 * @author      Jpay
 */
class PaymentInformationManagement {
    /** @var \Jpay\Payments\Logger\Logger */
    private $log;
    /** @var \Jpay\Payments\Helper\JumiaPay */
    private $helper;

    /**
     * Constructor
     *
     * @param \Jpay\Payments\Logger\Logger $jpayLogger
     * @param \Jpay\Payments\Helper\Payment $helper
     */
    public function __construct(\Jpay\Payments\Logger\Logger $jpayLogger, \Jpay\Payments\Helper\JumiaPay $helper) {
        $this->log = $jpayLogger;
        $this->helper = $helper;
    }


    /**
     * Set payment information and place order for a specified cart.
     *
     * @param \Magento\Checkout\Model\PaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return string JSON encoded payment details
     */
    public function aroundSavePaymentInformationAndPlaceOrder( \Magento\Checkout\Model\PaymentInformationManagement $subject
        , \Closure $proceed
        , $cartId
        , \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
        , \Magento\Quote\Api\Data\AddressInterface $billingAddress)
    {
        /* Execute the normal Magento 2 method and save the order ID. */
        $orderId = $proceed($cartId, $paymentMethod, $billingAddress);

        if ($paymentMethod->getMethod() == \Jpay\Payments\Model\JPay::METHOD_CODE){
                $this->log->info(__FUNCTION__ . __(" Processing order #%1", $orderId));

                try {
                        $checkoutUrl = $this->helper->createPurchase($orderId);
                } catch(\Magento\Framework\Validator\Exception $e) {
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $_checkoutSession = $objectManager->create('\Magento\Checkout\Model\Session');
                        $_checkoutSession->restoreQuote();

                        throw new \Magento\Framework\Exception\CouldNotSaveException(
                                __($e->getMessage()),
                                $e
                        );
                }

                return $checkoutUrl;
        }
    }
}
