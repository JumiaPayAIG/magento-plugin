<?php
namespace Jpay\Payments\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Jpay payment method configuration provider
 *
 * @category    Jpay\Payments\Model
 * @package     Jpay_Payments
 * @author      Jpay
 * @codingStandardsIgnoreFile
 */
class ConfigProvider implements ConfigProviderInterface {
    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig() {
        $outConfig = ['payment' => [\Jpay\Payments\Model\Jpay::METHOD_CODE => '']];

        return $outConfig;
    }
}
