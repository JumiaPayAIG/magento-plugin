<?php
namespace Jumia\jPay\Controller\Satus;

use Magento\Framework\App\Action\Context;
use Jumia\jPay\Logger\Logger;

class Index extends  \Magento\Framework\App\Action\Action
{


    protected $_pageFactory;
    private $logger;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Logger $logger,
        \Magento\Framework\View\Result\PageFactory $pageFactory

    )
    {
        $this->_pageFactory = $pageFactory;
        $this->logger = $logger;
         parent::__construct($context);
    }

    public function execute()
    {

        if(isset($_POST)){


           echo "test";

       }
    }

}
