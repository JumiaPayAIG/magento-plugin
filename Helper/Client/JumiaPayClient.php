<?php

namespace Jpay\Payments\Helper\Client;

use Magento\Sales\Model\Order;
use Magento\Framework\App\ObjectManager;

/**
 * Helper class for everything that has to do with payment
 *
 * @package Jpay\Payments\Helper
 * @author Jpay
 */
class JumiaPayClient {

    /** @var \Jpay\Payments\Logger\Logger */
    private $log;

    protected $messageManager;

    /**
     * Constructor
     *
     * @param \Jpay\Payments\Model\Config $config
     * @param \Jpay\Payments\Logger\Logger $jpayLogger
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory $transactions
     */
    public function __construct(
        \Jpay\Payments\Logger\Logger $jpayLogger
        , \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->log = $jpayLogger;
        $this->messageManager = $messageManager;
    }

    public function makeCreatePurchaseRequest($endpoint, $headers, $body) {

        $curl = curl_init($endpoint);

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //curl error SSL certificate problem, verify that the CA cert is OK

        $result		= curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $response	= json_decode($result, true);


        if ($httpcode != 200) {
            if (isset($response['payload'][0]['description'])) {
                $this->messageManager->addErrorMessage($response['payload'][0]['description']);
                throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase($response['payload'][0]['description']));
            }

            $this->messageManager->addErrorMessage("Error Conecting to JumiaPay");
            throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase("Error Conecting to JumiaPay"));
        }

        return $response['payload']['checkoutUrl'];
    }

    public function makeRefundRequest($endpoint, $headers, $body) {

      $curl = curl_init($endpoint);

      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //curl error SSL certificate problem, verify that the CA cert is OK

      $result		= curl_exec($curl);
      $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      $response	= json_decode($result, true);


      if ($httpcode != 200) {
          if (isset($response['payload'][0]['description'])) {
              $this->messageManager->addErrorMessage($response['payload'][0]['description']);
              throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase($response['payload'][0]['description']));
          }

          $this->messageManager->addErrorMessage("Error Conecting to JumiaPay");
          throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase("Error Conecting to JumiaPay"));
      }
    }
}
