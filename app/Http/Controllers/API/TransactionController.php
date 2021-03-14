<?php

namespace App\Http\Controllers\API;

use Midtrans\Snap;
use Midtrans\Config;
use Illuminate\Http\Request;
use App\Models\MsTransaction;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');

        if ($id) {
            $transaction = MsTransaction::with(['food', 'user'])->find($id);

            if ($transaction) {
                return ResponseFormatter::success(
                    $transaction,
                    'Transaction data is retrieved successfuly.'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Transaction data is not exist.',
                    404
                );
            }
        }

        $transaction = MsTransaction::with(['food', 'user'])->where('user_id', Auth::user()->id);

        if ($food_id) {
            $transaction->where('food_id', $food_id);
        }
        if ($status) {
            $transaction->where('status', $status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'List of Transaction data is retrieved successfuly.'
        );
    }

    public function update(Request $request, $id)
    {
        $transaction = MsTransaction::findOrFail($id);
        $transaction->update($request->all());

        return ResponseFormatter::success($transaction, 'Transaction updated sucessfully.');
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'food_id' => 'required|exists:ms_foods,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required',
        ]);

        $transaction = MsTransaction::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status,
            'payment_url' => '',
        ]);

        // Midtrans Configuration
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // Retrieve created transaction
        $transaction = MsTransaction::with(['food', 'user'])->find($transaction->id);

        // Create transaction with Midtrans
        $midtrans = [
            'transaction_details' => [
                'order_id' => $transaction->id,
                'gross_amount' => (int) $transaction->total
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email
            ],
            'enabled_payments' => ['gopay', 'bank_transfer'],
            'vtweb' => []
        ];

        // Calling Midtrans
        try {
            // Retrieve Midtrans payment page
            $paymentURL = Snap::createTransaction($midtrans)->redirect_url;

            $transaction->payment_url = $paymentURL;
            $transaction->save();

            // Return data to API
            return ResponseFormatter::success($transaction, 'Transaction Successfully.');
        } catch (\Exception $error) {
            return ResponseFormatter::error($error->getMessage(), 'Transaction Failed');
        }
    }
}
