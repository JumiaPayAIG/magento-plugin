<?php

namespace Jpay\Payments\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Formatter\LineFormatter;

/**
 * Handler for Jpay logs
 * @package Jpay\Payments\Logger
 * @author Jpay
 * @codingStandardsIgnoreFile
 */
class Handler extends \Magento\Framework\Logger\Handler\Base {
    /**  @var int: Logging level */
    protected $loggerType = \Monolog\Logger::DEBUG;

    /** @var string: File name */
    protected $fileName = '/var/log/jpay.log';


    /**
     * Constructor
     *
     * @param DriverInterface $filesystem
     * @param string $filePath
     */
    public function __construct(DriverInterface $filesystem,$filePath = null) {
        parent::__construct($filesystem, $filePath);
        $this->setFormatter(new LineFormatter(null, null, true, true));
    }
}
