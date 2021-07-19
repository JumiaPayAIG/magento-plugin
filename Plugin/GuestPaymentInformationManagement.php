<?php

namespace Jpay\Payments\Plugin;

/**
 * Jpay payment method  CSRF validator
 *
 * @category    Jpay\Payments\Plugin
 * @package     Jpay_Payments
 * @author      Jpay
 */
class GuestPaymentInformationManagement {
  /** @var \Jpay\Payments\Logger\Logger */
  private $log;
  /** @var \Jpay\Payments\Helper\JumiaPay */
  private $helper;

  protected $messageManager;

  /**
   * Constructor
   *
   * @param \Jpay\Payments\Logger\Logger $jpayLogger
   * @param \Jpay\Payments\Helper\Payment $helper
   */
  public function __construct(
      \Jpay\Payments\Logger\Logger $jpayLogger,
      \Jpay\Payments\Helper\JumiaPay $helper,
      \Magento\Framework\Message\ManagerInterface $messageManager
  ) {
    $this->log = $jpayLogger;
    $this->helper = $helper;
    $this->messageManager = $messageManager;
  }


  /**
   * Set payment information and place order for a specified cart.
   *
   * @param \Magento\Checkout\Model\GuestPaymentInformationManagement $subject
   * @param \Closure $proceed
   * @param $cartId
   * @param $email
   * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
   * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
   * @throws \Magento\Framework\Exception\CouldNotSaveException
   * @return string JSON encoded payment details
   */
  public function aroundSavePaymentInformationAndPlaceOrder( \Magento\Checkout\Model\GuestPaymentInformationManagement $subject
                                                           , \Closure $proceed
                                                           , $cartId
                                                           , $email
                                                           , \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
                                                           , \Magento\Quote\Api\Data\AddressInterface $billingAddress)
  {
    /* Execute the normal Magento 2 method and save the order ID. */
    $orderId = $proceed($cartId, $email, $paymentMethod, $billingAddress);

    $this->log->info(__FUNCTION__ . __(" Processing order #%1", $orderId));

    return $this->helper->createPurchase($orderId);
  }
}
