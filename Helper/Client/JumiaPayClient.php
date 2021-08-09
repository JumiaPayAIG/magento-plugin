<?php

namespace Jpay\Payments\Helper\Client;

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

    private function makeRequest($endpoint, $headers, $body) {

        $this->log->info(__FUNCTION__ . __('Star jumia pay request'));

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

        curl_close($curl);

        if ($httpcode != 200) {
            if (isset($response['payload'][0]['description'])) {
                throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase($response['payload'][0]['description']));
            }

            throw new \Magento\Framework\Validator\Exception(new \Magento\Framework\Phrase("Error Conecting to JumiaPay"));
        }

        return $response;
    }

    public function makeCreatePurchaseRequest($endpoint, $headers, $body) {

        $this->log->info(__FUNCTION__);
        $response = $this->makeRequest($endpoint, $headers, $body);

        return ['checkoutUrl' => $response['payload']['checkoutUrl'], 'purchaseId' => $response['payload']['purchaseId']];
    }

    public function makeRefundRequest($endpoint, $headers, $body) {
        $this->log->info(__FUNCTION__);
        $this->makeRequest($endpoint, $headers, $body);
    }

    public function makeCancelRequest($endpoint, $headers, $body) {
        $this->log->info(__FUNCTION__);
        $this->makeRequest($endpoint, $headers, $body);
    }
}
