<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Jumia\jPay\Model;


class JumiaPaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

	protected $_isInitializeNeeded      = false;
    protected $redirect_uri;
    protected $_code = 'jumia';
 	protected $_canOrder = true;
	protected $_isGateway = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;



    public function getOrderPlaceRedirectUrl() {
	   return \Magento\Framework\App\ObjectManager::getInstance()
							->get('Magento\Framework\UrlInterface')->getUrl("jumia/redirect");
   }
//    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
//    {
//       // some code
////        $transactionId = $payment->getParentTransactionId();
////        $payment
////            ->setTransactionId($transactionId . '-' . \Magento\Sales\Model\Order\Payment\Transaction::TYPE_REFUND)
////            ->setParentTransactionId($transactionId)
////            ->setIsTransactionClosed(1)
////            ->setShouldCloseParentTransaction(1);
//
//        return $this;
//    }
}

