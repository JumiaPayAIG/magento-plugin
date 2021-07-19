/**
 * Jpay_Payments Magento JS component
 *
 * @category    Jpay
 * @package     Jpay_Payments
 * @author      Jpay
 */
define(
  [
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
  ],
  function (
    Component,
    rendererList
  ) {
    'use strict';
    rendererList.push(
      {
        type: 'jpay',
        component: 'Jpay_Payments/js/view/payment/method-renderer/jpay-method'
      }
    );
    /** Add view logic here if needed */
    return Component.extend({});
  }
);
