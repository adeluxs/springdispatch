<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Order;
use App\Http\Resources\API\PaymentResource;
use App\Models\Wallet;
use App\Models\WalletHistory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Http\Resources\API\DeliveryManEarningResource;

class PaymentController extends Controller
{
    public function paymentSave(Request $request)
    {
        $data = $request->all();
        // \Log::info('save-payment'.json_encode($data));
        $data['datetime'] = isset($request->datetime) ? date('Y-m-d H:i:s',strtotime($request->datetime)) : date('Y-m-d H:i:s');

        if( request('payment_type') == 'wallet' ) {
            $wallet = Wallet::where('user_id', request('client_id'))->first();
            if($wallet != null) {
                if($wallet->total_amount <= request('total_amount')) {
                    $message = __('message.balance_insufficient');
                    return json_message_response($message,400);
                }
                $data['payment_status'] = 'paid';
            } else {
                $message = __('message.not_found_entry',['name' => __('message.wallet')]);
                return json_message_response($message,400);
            }
        }

        try {
            DB::beginTransaction();
            if( !in_array(request('payment_type'), ['cash','wallet']) ) {
                $data['received_by'] = 'admin';
            }
            $result = Payment::updateOrCreate(['id' => $request->id],$data);
            if( $result->payment_status == 'paid') {
                if( $result->payment_type == 'wallet') {
                    $wallet->decrement('total_amount', $result->total_amount );
                    $order = $result->order;
                    
                    $admin_id = User::admin()->id;
                    $currency = appSettingData('currency_code');
                    $client_wallet = Wallet::where('user_id', $order->client_id)->first();
                    $client_wallet_history = [
                        'user_id'           => $order->client_id,
                        'type'              => 'debit',
                        'currency'          => $currency,
                        'transaction_type'  => 'order_fee',
                        'amount'            => $result->total_amount,
                        'balance'           => $client_wallet->total_amount,
                        'order_id'          => $result->order_id,
                        'datetime'          => date('Y-m-d H:i:s'),
                        'data' => [
                            'payment_id'    => $result->id,
                        ]
                    ];
                
                    WalletHistory::create($client_wallet_history);

                    $admin_wallet = Wallet::firstOrCreate(
                        [ 'user_id' => $admin_id ]
                    );
                    $admin_wallet->increment('total_amount', $result->total_amount );
                    $admin_wallet_history = [
                        'user_id'           => $admin_id,
                        'type'              => 'credit',
                        'currency'          => $currency,
                        'transaction_type'  => 'order_fee',
                        'amount'            => $result->total_amount,
                        'balance'           => $admin_wallet->total_amount,
                        'order_id'          => $result->order_id,
                        'datetime'          => date('Y-m-d H:i:s'),
                        'data' => [
                            'payment_id'    => $result->id,
                        ]
                    ];
                    WalletHistory::create($admin_wallet_history);
                }
            }
            DB::commit();
        } catch(\Exception $e) {
            \Log::info($e);
            DB::rollBack();
            return json_custom_response($e);
        }
        
        $order = Order::find($request->order_id);
        $order->payment_id = $result->id;
        
        $order->save();
        
        $status_code = 200;
        if($result->payment_status == 'paid')
        {
            $message = __('message.payment_completed');
        } else {
            $message = __('message.payment_status_message',['status' => __('message.'.$result->payment_status), 'id' => $order->id  ]);
        }

        if($result->payment_status == 'failed')
        {
            $status_code = 400;
        }
        
        $history_data = [
            'history_type' => 'payment_status_message',
            'payment_status'=> $result->payment_status,
            'order_id' => $order->id,
            'order' => $order,
        ];

        saveOrderHistory($history_data);

        return json_message_response($message,$status_code);
    }

    public function getList(Request $request)
    {
        $payment = Payment::myPayment();

        $payment->when(request('delivery_man_id'), function ($query) {
            return $query->whereHas('order', function ($q) {
                $q->where('delivery_man_id',request('delivery_man_id'));
            });
        });

        $payment->when(request('type') == 'earning', function ($query) {
            return $query->whereHas('order', function ($q) {
                $q->whereIn('status',['completed', 'cancelled']);
            });
        });
        $payment->when(request('client_id'), function ($query) {
            $query->where('client_id',request('client_id'));
        });

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            
            if(is_numeric($request->per_page))
            {
                $per_page = $request->per_page;
            }

            if($request->per_page == -1 ){
                $per_page = $payment->count();
            }
        }

        $payment = $payment->orderBy('id','desc')->paginate($per_page);
        $items = PaymentResource::collection($payment);

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];
        
        return json_custom_response($response);
    }

    public function getDeliveryManEarningList(Request $request)
    {
        $delivery_earning = User::select('users.id','users.name')->where('user_type', 'delivery_man')->has('deliveryManOrder')
            ->with(['getPayment:order_id,delivery_man_commission,admin_commission', 'userWallet:total_amount,total_withdrawn'])
            ->withCount(['deliveryManOrder as total_order',
                    'getPayment as paid_order' => function ($query) {
                        $query->where('payment_status', 'paid');
                    }]
            )
            ->withSum('userWallet as wallet_balance', 'total_amount')
            ->withSum('userWallet as total_withdrawn', 'total_withdrawn')
            ->withSum('getPayment as delivery_man_commission', 'delivery_man_commission')
            ->withSum('getPayment as admin_commission', 'admin_commission');

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page))
        {
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $delivery_earning->count();
            }
        }
        
        $delivery_earning = $delivery_earning->orderBy('id','desc')->paginate($per_page);

        $items = DeliveryManEarningResource::collection($delivery_earning);

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];
        
        return json_custom_response($response);
    }
}
