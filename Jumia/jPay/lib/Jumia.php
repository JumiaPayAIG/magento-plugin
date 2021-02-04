<?php
/**
 * Instamojo
 * used to manage Instamojo API calls
 * 
 */
include_once __DIR__ . DIRECTORY_SEPARATOR . "curl.php";
include_once __DIR__ . DIRECTORY_SEPARATOR . "ValidationException.php";

use \ValidationException as ValidationException;
use \Exception as Exception;


Class Jumia
{
	private $api_endpoint;
	private $auth_endpoint;
	private $auth_headers;
	private $access_token;
	private $client_id;
	private $client_secret;


	 function __construct($environment,$country_list,$shop_config_key,$api_key,$logger,$data,$headers)
	{
        $this->logger = $logger;
        $this->data = $data;
        $this->headers = $headers;
		$this->curl = new Curl();
		$this->curl->setCacert(__DIR__."/cacert.pem");
		$this->country_list 		= $country_list;
		$this->shop_config_key	= $shop_config_key;

        $this->api_key	= $api_key;
        if($environment == "Live"){
            $this->api_endpoint  = "h​ttps://api-staging-pay.jumia.com.ng";
        }
        if($environment == "Sandbox"){
            $this->api_endpoint  = "h​ttps://api-staging-pay.jumia.com.ng";
        }

		$this->getAccessToken();
	}
	public function getAccessToken()
	{
        $data= json_encode($this->data, JSON_FORCE_OBJECT);

//        $headers=array("apikey: X1w51boOivgwnV4QoHbWdKBlQ2MwBZBhYVpwL2PQLVLdZ3JV6Ekjg51c9Kd2FjWo","Content-type: application/json");

        $endpoint ="https://api-staging-pay.jumia.com.ng/merchant/create";

        $curl = curl_init($endpoint);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //curl error SSL certificate problem, verify that the CA cert is OK

        $result		= curl_exec($curl);
        $response	= json_decode($result);
        var_dump($response);
        curl_close($curl);
        $this->logger->info('resultt ='.print_r($response,true));
        $payload=$response->payload;
        var_dump($payload);
        $this->logger->info('$checkoutUrl ='.print_r($payload,true));
        $checkoutUrl=$payload->checkoutUrl;
        var_dump($checkoutUrl);
        $this->logger->info('$checkoutUrl ='.print_r($checkoutUrl,true));
//        $resultRedirect = $this->resultRedirectFactory->create();
//        $resultRedirect->setUrl($checkoutUrl);
        header("Location:".$checkoutUrl );
//        function redirect($checkoutUrl) {
//            ob_start();
//            header('Location: '.$checkoutUrl);
//            ob_end_flush();
//            die();
//        }
//        return $resultRedirect;
		//$result = $this->curl->post($this->api_endpoint,$data);
//		if($result)
//		{
//			$result = json_decode($result);
//			if(isset($result->error))
//			{
//				throw new ValidationException("The Authorization request failed with message '$result->error'",array("Payment Gateway Authorization Failed."),$result);
//			}else
//				$this->access_token = 	$result->access_token;
//		}
//
//		$this->auth_headers[] = "Authorization:Bearer $this->access_token";

	}

//	public function createOrderPayment($data)
//	{
//		//$endpoint = $this->api_endpoint ."/merchant/create";
//        $endpoint ="http://jsonplaceholder.typicode.com/posts";
//        $result = $this->curl->post($endpoint,array());
//        $this->logger->info('result ='.print_r($result,true));
//
//		if(isset($result->order))
//		{
//
//			return $result;
//		}else{
//			$errors = array();
//			if(isset($result->message))
//				throw new ValidationException("Validation Error with message: $result->message",array($result->message),$result);
//
//			foreach($result as $k=>$v)
//			{
//				if(is_array($v))
//					$errors[] =$v[0];
//			}
//			if($errors)
//				throw new ValidationException("Validation Error Occurred with following Errors : ",$errors,$result);
//		}
//	}
//
//
//	public function getOrderById($id)
//	{
//		$endpoint = $this->api_endpoint."gateway/orders/id:$id/";
//		$result = $this->curl->get($endpoint,array("headers"=>$this->auth_headers));
//
//		$result = json_decode($result);
//		if(isset($result->id) and $result->id)
//			return $result;
//		else
//			throw new Exception("Unable to Fetch Payment Request id:'$id' Server Responds ".print_R($result,true));
//	}
//
//	public function getPaymentStatus($payment_id, $payments){
//		foreach($payments as $payment){
//		    if($payment->id == $payment_id){
//			    return $payment->status;
//		    }
//		}
//	}
	
}
