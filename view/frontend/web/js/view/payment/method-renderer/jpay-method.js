/**
 * Jpay_Payments Magento JS component
 *
 * @category    Jpay
 * @package     Jpay_Payments
 * @author      Jpay
 */

define(
  [
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/place-order',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer'
  ],
  function (
      $,
      Component,
      urlBuilder,
      storage,
      fullScreenLoader,
      placeOrderAction,
      additionalValidators,
      quote,
      customer
  ) {
    'use strict';
    var wpConfig = window.checkoutConfig.payment.jpay;

    return Component.extend({
      defaults: {
        template: 'Jpay_Payments/payment/jpay'
      },

      redirectAfterPlaceOrder: false,

      placeOrder: function (data, event) {
        var self = this;

        if (event) {
          event.preventDefault();
        }

        if (this.validate() && additionalValidators.validate()) {
          this.isPlaceOrderActionAllowed(false);

          this.getPlaceOrderDeferredObject()
              .fail(
                function () {
                  self.isPlaceOrderActionAllowed(true);
                }
              ).done(
                function (response) {
                  console.log(response);
                  window.location.replace(response);
                }
              );

          return true;
        }

        return false;
      }
    });
  }
);
