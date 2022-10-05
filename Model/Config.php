<?php
namespace Jpay\Payments\Model;

/**
 * Jpay payment method configuration
 *
 * @category    Jpay\Payments\Model
 * @package     Jpay_Payments
 * @author      Jpay
 * @codingStandardsIgnoreFile
 */
class Config{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfigInterface;

    /* The URLs for production and staging. */
    private $live_host_name = 'https://api-pay.jumia';
    private $stage_host_name = 'https://api-staging-pay.jumia';

    /**
     * Function used for reading a config value.
     */
    private function getConfigValue($value){
        return $this->scopeConfigInterface->getValue('payment/jpay/' . $value);
    }


    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $configInterface){
        $this->scopeConfigInterface = $configInterface;
    }


    public function getLiveMode(){
        return $this->getConfigValue('live_mode');
    }


    public function getCountry() {
        return $this->getConfigValue('country');
    }

    public function getPayApiKey() {
        if ($this->getLiveMode()) {
            return $this->getConfigValue('live_api_key');
        } else {
            return $this->getConfigValue('sandbox_api_key');
        }
    }

    public function getShopConfigID() {
        if ($this->getLiveMode()) {
            return $this->getConfigValue('live_shop_config_id');
        } else {
            return $this->getConfigValue('sandbox_shop_config_id');
        }
    }

    public function getShopKey() {
        if ($this->getLiveMode()) {
            return $this->getConfigValue('live_shop_key');
        } else {
            return $this->getConfigValue('sandbox_shop_key');
        }
    }

    public function getHost() {
        switch ($this->getCountry()) {
        case 'ng':
            return $this->getApiHost().'.com.ng';
            break;
        case 'eg':
            return $this->getApiHost().'.com.eg';
            break;
        case 'ma':
            return $this->getApiHost().'.ma';
            break;
        case 'ke':
            return $this->getApiHost().'.co.ke';
            break;
        case 'gh':
            return $this->getApiHost().'.com.gh';
            break;
        case 'ci':
            return $this->getApiHost().'.ci';
            break;
        case 'tn':
            return $this->getApiHost().'.com.tn';
            break;
        case 'ug':
            return $this->getApiHost().'.ug';
            break;
        case 'dz':
            return $this->getApiHost().'.dz';
            break;
        case 'sn':
            return $this->getApiHost().'.sn';
            break;
        default:
            return '';
            break;
        }
    }

    public function getReturnUrl(){
        return 'jpay/payment/backurl';
    }

    public function getCallBackUrl(){
        return 'jpay/payment/ipn';
    }

    protected function getApiHost(){
        if(1 == $this->getLiveMode()){
            return $this->live_host_name;
        } else {
            return $this->stage_host_name;
        }
    }

}
