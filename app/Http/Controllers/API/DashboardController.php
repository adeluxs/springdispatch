<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Hash;
Use Auth;
use App\Models\User;
use App\Models\Country;
use App\Models\City;
use App\Models\Order;
use App\Models\AppSetting;
use App\Models\UserBankAccount;
use App\Models\Wallet;
use App\Models\Payment;
use Carbon\Carbon;
use App\Http\Resources\API\UserResource;
use App\Http\Resources\API\OrderResource;

class DashboardController extends Controller
{
    public function clientDashboard(Request $request)
    {
        $dashboard_data = [];
        $user = auth()->user();
        $dashboard_data['total_order'] = Order::myOrder()->count();

        $dashboard_data['app_setting'] = AppSetting::first();
        $wallet_data = Wallet::where('user_id', $user->id)->first();
        $dashboard_data['wallet_data'] = $wallet_data ?? null;
        $dashboard_data['total_amount'] = $wallet_data->total_amount ?? 0;

        $upcoming_order = Order::myOrder()->whereNotIn('status',['draft', 'cancelled', 'completed'])->whereNotNull('pickup_point->start_time')
                        ->where('pickup_point->start_time','>=',Carbon::now()->format('Y-m-d H:i:s'))
                        ->orderBy('pickup_point->start_time','asc')->paginate(10);
        $dashboard_data['upcoming_order'] = OrderResource::collection($upcoming_order);

        $dashboard_data['counts'] = $user->unreadNotifications->where('created_at', '>', $user->last_notification_seen)->count();
        $dashboard_data['all_unread_count'] = isset($user->unreadNotifications) ? $user->unreadNotifications->count() : 0;
        
        return json_custom_response($dashboard_data);
    }
}