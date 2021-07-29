<?php

namespace Jpay\Payments\Helper\Client;

use Magento\Sales\Model\Order;
use Magento\Framework\App\ObjectManager;

class JumiaPayClient {

    /** @var \Jpay\Payments\Logger\Logger */
    private $log;

    protected $messageManager;

    public function __construct(
        \Jpay\Payments\Logger\Logger $jpayLogger
        , \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->log = $jpayLogger;
        $this->messageManager = $messageManager;
    }

    public function makeCreatePurchaseRequest($endpoint, $headers, $body) {

        $this->log->info(__FUNCTION__ . __('Start create purchase request'));

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

        return ['checkoutUrl' => $response['payload']['checkoutUrl'], 'purchaseId' => $response['payload']['purchaseId']];
    }

    public function makeRefundRequest($endpoint, $headers, $body) {

        $this->log->info(__FUNCTION__ . __('Start refund / cancel request'));

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
