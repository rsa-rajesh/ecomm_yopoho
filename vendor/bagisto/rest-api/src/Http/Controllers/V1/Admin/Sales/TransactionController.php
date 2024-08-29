<?php

namespace Webkul\RestApi\Http\Controllers\V1\Admin\Sales;

use Illuminate\Http\Request;
use Webkul\RestApi\Http\Resources\V1\Admin\Sales\OrderTransactionResource;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

class TransactionController extends SalesController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected ShipmentRepository $shipmentRepository
    ) {
        parent::__construct();
    }

    /**
     * Repository class name.
     */
    public function repository(): string
    {
        return OrderTransactionRepository::class;
    }

    /**
     * Resource class name.
     */
    public function resource(): string
    {
        return OrderTransactionResource::class;
    }

    /**
     * Save the transaction.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'invoice_id'     => 'required',
            'payment_method' => 'required',
            'amount'         => 'required|numeric|min:0.0001',
        ]);

        $invoice = $this->invoiceRepository->where('increment_id', $request->invoice_id)->first();

        if (! $invoice) {
            return response([
                'message' => trans('rest-api::app.admin.sales.transactions.invoice-missing'),
            ], 400);
        }

        if ($invoice->state == 'paid') {
            return response([
                'message' => trans('rest-api::app.admin.sales.transactions.already-paid'),
            ], 400);
        }

        $transactionTotal = $this->getRepositoryInstance()->where('invoice_id', $invoice->id)->sum('amount');

        $transactionAmtFinal = $request->amount + $transactionTotal;

        if ($transactionAmtFinal > $invoice->base_grand_total) {
            return response([
                'message' => trans('rest-api::app.admin.sales.transactions.transaction-amount-exceeds'),
            ], 400);
        }

        $order = $this->orderRepository->find($invoice->order_id);

        $data = [
            'paidAmount' => $request->amount,
        ];

        $randomId = random_bytes(20);
        $transactionId = bin2hex($randomId);

        $transactionData['transaction_id'] = $transactionId;
        $transactionData['type'] = $request->payment_method;
        $transactionData['payment_method'] = $request->payment_method;
        $transactionData['invoice_id'] = $invoice->id;
        $transactionData['order_id'] = $invoice->order_id;
        $transactionData['amount'] = $request->amount;
        $transactionData['status'] = 'paid';
        $transactionData['data'] = json_encode($data);

        $transaction = $this->getRepositoryInstance()->create($transactionData);

        $transactionTotal = $this->getRepositoryInstance()->where('invoice_id', $invoice->id)->sum('amount');

        if ($transactionTotal >= $invoice->base_grand_total) {
            $shipments = $this->shipmentRepository->where('order_id', $invoice->order_id)->first();

            if (isset($shipments)) {
                $this->orderRepository->updateOrderStatus($order, 'completed');
            } else {
                $this->orderRepository->updateOrderStatus($order, 'processing');
            }

            $this->invoiceRepository->updateState($invoice, 'paid');
        }

        return response([
            'message' => trans('rest-api::app.admin.sales.transactions.transaction-saved'),
            'data'    => new OrderTransactionResource($transaction),
        ]);
    }
}
