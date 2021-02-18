<?php

namespace Jumia\jPay\Controller\Invoice;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Jumia\jPay\Logger\Logger;

class Index extends Action
{
    protected $orderRepository;
    protected $invoiceService;
    protected $transaction;
    protected $invoiceSender;
    private $logger;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Logger $logger,
        Transaction $transaction
    )
    {
        $this->orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->logger->info("invoiceService test");
        $this->logger->info("order id = ".$_GET['orderid']);
        $orderId = $_GET['orderid']; //it should be order id
        $order = $this->orderRepository->get($orderId);
        if ($order->canInvoice()) {
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->pay()->save();
            $transactionSave = $this->transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
            $this->invoiceSender->send($invoice);

        }
    }
}