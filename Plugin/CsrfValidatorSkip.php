<?php
namespace Jpay\Payments\Plugin;

/**
 * Jpay payment method  CSRF validator
 *
 * @category    Jpay\Payments\Plugin
 * @package     Jpay_Payments
 * @author      Jpay
 */
class CsrfValidatorSkip {
  /**
   * @param \Magento\Framework\App\Request\CsrfValidator $subject
   * @param \Closure $proceed
   * @param \Magento\Framework\App\RequestInterface $request
   * @param \Magento\Framework\App\ActionInterface $action
   */
  public function aroundValidate($subject, \Closure $proceed, $request, $action) {
    if ($request->getModuleName() == 'jpay') {
      /* Skip CSRF check. */
      return;
    } else {
      /* Proceed Magento 2 core functionalities. */
      $proceed($request, $action);
    }
  }
}
