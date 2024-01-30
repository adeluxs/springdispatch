<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\UserRequest;
use Validator;
use Hash;
Use Auth;
use App\Http\Resources\API\UserResource;
use Illuminate\Support\Facades\Password;
use App\Models\Country;
use App\Models\City;
use App\Models\Order;
use App\Http\Resources\API\OrderResource;
use Carbon\Carbon;
use App\Models\AppSetting;
use App\Models\DeliveryManDocument;
use App\Models\Payment;
use App\Models\UserBankAccount;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\API\UserDetailResource;
use App\Http\Resources\API\WalletHistoryResource;
use App\Http\Resources\API\DeliveryManEarningResource;
use App\Http\Resources\API\PaymentResource;
use App\Http\Requests\UserUpdateRequest;
use App\Notifications\EmailVerification;
use App\Models\VerificationCode;

class UserController extends Controller
{

    public function dashboard(Request $request)
    {
        $dashboard_data = [];
        $dashboard_data['total_country'] = Country::count();
        $dashboard_data['total_city'] = City::count();
        $dashboard_data['total_client'] = User::userCount('client');
        $dashboard_data['total_delivery_man'] = User::userCount('delivery_man');
        $dashboard_data['total_order'] = Order::myOrder()->count();
        $dashboard_data['today_register_user'] = User::where('user_type','client')->whereDate('created_at',today())->count();
               
        $total_compeleted_earning = Order::myOrder()->where('status', 'completed')->sum('total_amount');
        $total_cancelled_earning = Order::myOrder()->where('status', 'cancelled')->sum('total_amount');
        
        $dashboard_data['total_earning'] = $total_compeleted_earning + $total_cancelled_earning;
        $dashboard_data['total_cancelled_order'] = Order::myOrder()->where('status', 'cancelled')->count();

        $dashboard_data['total_create_order'] = Order::myOrder()->where('status', 'create')->count();
        $dashboard_data['total_active_order'] = Order::myOrder()->where('status', 'active')->count();
        $dashboard_data['total_delayed_order'] = Order::myOrder()->where('status', 'delayed')->count();
        $dashboard_data['total_courier_assigned_order'] = Order::myOrder()->where('status', 'courier_assigned')->count();
        $dashboard_data['total_courier_picked_up_order'] = Order::myOrder()->where('status', 'courier_picked_up')->count();
        $dashboard_data['total_courier_departed_order'] = Order::myOrder()->where('status', 'courier_departed')->count();
        $dashboard_data['total_courier_arrived_order'] = Order::myOrder()->where('status', 'courier_arrived')->count();
        $dashboard_data['total_completed_order'] = Order::myOrder()->where('status', 'completed')->count();
        $dashboard_data['total_failed_order'] = Order::myOrder()->where('status', 'failed')->count();

        $dashboard_data['today_create_order'] = Order::myOrder()->whereDate('created_at',today())->where('status', 'create')->count();
        $dashboard_data['today_active_order'] = Order::myOrder()->whereDate('created_at',today())->where('status', 'active')->count();
        $dashboard_data['today_delayed_order'] = Order::myOrder()->whereDate('created_at',today())->where('status', 'delayed')->count();
        $dashboard_data['today_cancelled_order'] = Order::myOrder()->whereDate('created_at',today())->where('status', 'cancelled')->count();
        $dashboard_data['today_courier_assigned_order'] = Order::myOrder()->whereDate('created_at',today())->where('status', 'courier_assigned')->count();
        $dashboard_data['today_courier_picked_up_order'] = Order::myOrder()->whereDate('created_at',today())->where('status', 'courier_picked_up')->count();
        $dashboard_data['today_courier_departed_order'] = Order::myOrder()->whereDate('created_at',today())->where('status', 'courier_departed')->count();
        $dashboard_data['today_courier_arrived_order'] = Order::myOrder()->whereDate('created_at',today())->where('status', 'courier_arrived')->count();
        $dashboard_data['today_completed_order'] = Order::myOrder()->whereDate('created_at',today())->where('status', 'completed')->count();
        $dashboard_data['today_failed_order'] = Order::myOrder()->whereDate('created_at',today())->where('status', 'failed')->count();

        $dashboard_data['app_setting'] = AppSetting::first();
        /*
        $upcoming_order = Order::myOrder()->whereDate('pickup_datetime','>=',Carbon::now()->format('Y-m-d H:i:s'))->orderBy('pickup_datetime','asc')->paginate(10);
        $dashboard_data['upcoming_order'] = OrderResource::collection($upcoming_order);
        */

        $upcoming_order = Order::myOrder()->whereNotIn('status',['draft', 'cancelled', 'completed'])->whereNotNull('pickup_point->start_time')
                        ->where('pickup_point->start_time','>=',Carbon::now()->format('Y-m-d H:i:s'))
                        ->orderBy('pickup_point->start_time','asc')->paginate(10);
        $dashboard_data['upcoming_order'] = OrderResource::collection($upcoming_order);

        $recent_order = Order::myOrder()->whereDate('date','<=',Carbon::now()->format('Y-m-d'))->orderBy('date','desc')->paginate(10);
        $dashboard_data['recent_order'] = OrderResource::collection($recent_order);

        $client = User::where('user_type','client')->orderBy('created_at','desc')->paginate(10);
        $dashboard_data['recent_client'] = UserResource::collection($client);

        $delivery_man = User::where('user_type','delivery_man')->orderBy('created_at','desc')->paginate(10);
        $dashboard_data['recent_delivery_man'] = UserResource::collection($delivery_man);

        $sunday = strtotime('sunday -1 week');
	    $sunday = date('w', $sunday) === date('w') ? $sunday + 7*86400 : $sunday;
        $saturday = strtotime(date('Y-m-d',$sunday).' +6 days');

        $week_start = date('Y-m-d 00:00:00',$sunday);
        $week_end = date('Y-m-d 23:59:59',$saturday);

        $dashboard_data['week'] = [
            'week_start'=> $week_start,
            'week_end'  => $week_end
        ];
        $weekly_order_count = Order::selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date' )
                        ->whereBetween('created_at', [ $week_start, $week_end ])
                        ->get()->toArray();
        
                        $data = [];
        
        $order_collection = collect($weekly_order_count);
        for($i = 0; $i < 7 ; $i++){
            $total = $order_collection->filter(function ($value, $key) use($week_start, $i){
                return $value['date'] == date('Y-m-d',strtotime($week_start. ' + ' . $i . 'day'));
            })->count();
            
            $data[] = [
                'day' => date('l', strtotime($week_start . ' + ' . $i . 'day')),
                'total' => $total,
                'date' => date('Y-m-d',strtotime($week_start. ' + ' . $i . 'day')),    
            ];
        }

        $dashboard_data['weekly_order_count'] = $data;

        $user_week_report = User::selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date' )
                        ->where('user_type','client')
                        ->whereBetween('created_at', [ $week_start, $week_end ])
                        ->get()->toArray();
        $data = [];
        
        $user_collection = collect($user_week_report);
        for($i = 0; $i < 7 ; $i++){
            $total = $user_collection->filter(function ($value, $key) use($week_start,$i){
                return $value['date'] == date('Y-m-d',strtotime($week_start. ' + ' . $i . 'day'));
            })->count();
            
            $data[] = [
                'day' => date('l', strtotime($week_start . ' + ' . $i . 'day')),
                'total' => $total,
                'date' => date('Y-m-d',strtotime($week_start. ' + ' . $i . 'day')),    
            ];
        }

        $dashboard_data['user_weekly_count'] = $data;
      
        $user = auth()->user();
        $dashboard_data['all_unread_count']  = isset($user->unreadNotifications) ? $user->unreadNotifications->count() : 0;
        
        $weekly_payment_report = Payment::myPayment()->selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date, total_amount ' )
                        ->where('payment_status','paid')
                        ->whereBetween('created_at', [ $week_start, $week_end ])
                        ->get()->toArray();
        $data = [];

        $payment_collection = collect($weekly_payment_report);
        for($i = 0; $i < 7 ; $i++){
            $total_amount = $payment_collection->filter(function ($value, $key) use($week_start,$i){
                return $value['date'] == date('Y-m-d',strtotime($week_start. ' + ' . $i . 'day'));
            })->sum('total_amount');
            
            $data[] = [
                'day' => date('l', strtotime($week_start . ' + ' . $i . 'day')),
                'total_amount' => $total_amount,
                'date' => date('Y-m-d',strtotime($week_start. ' + ' . $i . 'day')),    
            ];
        }

        $dashboard_data['weekly_payment_report'] = $data;

        $month_start = Carbon::now()->startOfMonth();
        $today = Carbon::now();
        $diff = $month_start->diffInDays($today) + 1; // $today->daysInMonth;

        $dashboard_data['month'] = [
            'month_start'=> $month_start,
            'month_end'  => $today,
            'diff' => $diff,
        ];
        $monthly_order_count = Order::selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date' )
                        ->whereBetween('created_at', [ $month_start, $today ])
                        ->get()->toArray();
        
        $monthly_order_count_data = [];
        
        $order_collection = collect($monthly_order_count);

        $monthly_payment_report = Payment::myPayment()->selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date, total_amount ' )
            ->where('payment_status','paid')
            ->whereBetween('created_at', [ $month_start, $today ])
            ->whereHas('order',function ($query) {
                $query->where('status','completed');
            })->withTrashed()
            ->get()->toArray();
        
        $monthly_payment_completed_order_data = [];
               
        $payment_collection = collect($monthly_payment_report);
        
        $monthly_payment_cancelled_report = Payment::myPayment()->selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date, cancel_charges ' )
            ->where('payment_status','paid')
            ->whereBetween('created_at', [ $month_start, $today ])
            ->whereHas('order',function ($query) {
                $query->where('status','cancelled');
            })->withTrashed()
            ->get()->toArray();
        
        $monthly_payment_cancelled_order_data = [];       
        $payment_cancelled_collection = collect($monthly_payment_cancelled_report);

        for($i = 0; $i < $diff ; $i++){
            $total = $order_collection->filter(function ($value, $key) use($month_start, $i){
                return $value['date'] == date('Y-m-d',strtotime($month_start. ' + ' . $i . 'day'));
            })->count();
            
            $monthly_order_count_data[] = [
                'total' => $total,
                'date' => date('Y-m-d',strtotime($month_start. ' + ' . $i . 'day')),    
            ];

            $total_amount = $payment_collection->filter(function ($value, $key) use($month_start,$i){
                return $value['date'] == date('Y-m-d',strtotime($month_start. ' + ' . $i . 'day'));
            })->sum('total_amount');
            
            $monthly_payment_completed_order_data[] = [
                'total_amount' => $total_amount,
                'date' => date('Y-m-d',strtotime($month_start. ' + ' . $i . 'day')),    
            ];

            $cancel_charges = $payment_cancelled_collection->filter(function ($value, $key) use($month_start,$i){
                return $value['date'] == date('Y-m-d',strtotime($month_start. ' + ' . $i . 'day'));
            })->sum('cancel_charges');
            
            $monthly_payment_cancelled_order_data[] = [ 
                'total_amount' => $cancel_charges,
                'date' => date('Y-m-d',strtotime($month_start. ' + ' . $i . 'day')),    
            ];
        }

        $dashboard_data['monthly_order_count'] = $monthly_order_count_data;
        $dashboard_data['monthly_payment_completed_report'] = $monthly_payment_completed_order_data;
        $dashboard_data['monthly_payment_cancelled_report'] = $monthly_payment_cancelled_order_data;

        $dashboard_data['country_city_data'] = Country::with('cities')->get();

        return json_custom_response($dashboard_data);
    }

    public function dashboardChartData(Request $request)
    {
        $type = request('type');
        $month_start = Carbon::parse(request('start_at'))->startOfMonth();
        $month_end = Carbon::parse(request('end_at'))->endOfMonth();
        
        $diff = $month_start->diffInDays($month_end) + 1;
        $dashboard_data['month'] = [
            'month_start'=> $month_start,
            'month_end'  => $month_end,
            'diff' => $diff,
        ];
        $data = [];
        if( $type == 'monthly_order_count' )
        {
            $monthly_order_count = Order::selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date' )
                ->whereBetween('created_at', [ $month_start, $month_end ])
                ->get()->toArray();
            
            $order_collection = collect($monthly_order_count);
            
            for($i = 0; $i < $diff ; $i++) {
                $total = $order_collection->filter(function ($value, $key) use($month_start, $i){
                    return $value['date'] == date('Y-m-d',strtotime($month_start. ' + ' . $i . 'day'));
                })->count();
                
                $data[] = [
                    'total' => $total,
                    'date' => date('Y-m-d',strtotime($month_start. ' + ' . $i . 'day')),    
                ];
            }
            $dashboard_data['monthly_order_count'] = $data;
        }

        if( $type == 'monthly_payment_completed_report' )
        {
            $monthly_payment_report = Payment::myPayment()->selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date, total_amount ' )
                ->where('payment_status','paid')
                ->whereBetween('created_at', [ $month_start, $month_end ])
                ->whereHas('order',function ($query) {
                    $query->where('status','completed');
                })->withTrashed()
                ->get()->toArray();

            $payment_collection = collect($monthly_payment_report);

            for($i = 0; $i < $diff ; $i++) {
                $total_amount = $payment_collection->filter(function ($value, $key) use($month_start,$i){
                    return $value['date'] == date('Y-m-d',strtotime($month_start. ' + ' . $i . 'day'));
                })->sum('total_amount');
                $data[] = [
                    'total_amount' => $total_amount,
                    'date' => date('Y-m-d', strtotime($month_start. ' + ' . $i . 'day')),    
                ];
            }
            $dashboard_data['monthly_payment_completed_report'] = $data;
        }

        if( $type == 'monthly_payment_cancelled_report' )
        {
            $monthly_payment_report = Payment::myPayment()->selectRaw('DATE_FORMAT(created_at , "%w") as days , DATE_FORMAT(created_at , "%Y-%m-%d") as date, cancel_charges ' )
                ->where('payment_status','paid')
                ->whereBetween('created_at', [ $month_start, $month_end ])
                ->whereHas('order',function ($query) {
                    $query->where('status','cancelled');
                })->withTrashed()
                ->get()->toArray();
            
            $payment_collection = collect($monthly_payment_report);
            
            for($i = 0; $i < $diff ; $i++) {
                $cancel_charges = $payment_collection->filter(function ($value, $key) use($month_start,$i){
                    return $value['date'] == date('Y-m-d',strtotime($month_start. ' + ' . $i . 'day'));
                })->sum('cancel_charges');

                $data[] = [
                    'total_amount' => $cancel_charges,
                    'date' => date('Y-m-d', strtotime($month_start. ' + ' . $i . 'day')),    
                ];
            }
            $dashboard_data['monthly_payment_cancelled_report'] = $data;
        }

        return json_custom_response($dashboard_data);
    }

    public function register(UserRequest $request)
    {
        $input = $request->all();
                
        $password = $input['password'];
        $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'client';
        $input['password'] = Hash::make($password);

        if( in_array($input['user_type'],['delivery_man']))
        {
            $input['status'] = isset($input['status']) ? $input['status']: 0;
        }
        $user = User::create($input);

        if( $request->has('user_bank_account') && $request->user_bank_account != null ) {
            $user->userBankAccount()->create($request->user_bank_account);
        }
        
        $message = __('message.save_form',['form' => __('message.'.$input['user_type']) ]);
        $user->api_token = $user->createToken('auth_token')->plainTextToken;
        
        $user_detail = User::where('id',$user->id)->first();
        $user->otp_verify_at = $user_detail->otp_verify_at ?? null;
        $user->email_verified_at = $user_detail->email_verified_at ?? null;
        $is_email_verification = SettingData('email_verification', 'email_verification') ?? "0";

        $response = [
            'message' => $message,
            'is_email_verification' => $is_email_verification,
            'data' => $user
        ];
        return json_custom_response($response);
    }

    public function login()
    {      
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){
            
            $user = Auth::user();

            if(request('player_id') != null){
                $user->player_id = request('player_id');
            }
            
            if(request('fcm_token') != null){
                $user->fcm_token = request('fcm_token');
            }

            $user->save();
            
            $success = $user;
            $success['api_token'] = $user->createToken('auth_token')->plainTextToken;
            $success['profile_image'] = getSingleMedia($user,'profile_image',null);
            $is_verified_delivery_man = false;
            if($user->user_type == 'delivery_man') {
                $is_verified_delivery_man = DeliveryManDocument::verifyDeliveryManDocument($user->id);
            }
            $success['is_verified_delivery_man'] = (int) $is_verified_delivery_man;
            unset($success['media']);

            $is_email_verification = SettingData('email_verification', 'email_verification') ?? "0";
            return json_custom_response([ 'data' => $success, 'is_email_verification' => $is_email_verification ], 200 );
        }
        else{
            $message = __('auth.failed');
            
            return json_message_response($message,400);
        }
    }

    public function userList(Request $request)
    {
        $user_type = isset($request['user_type']) ? $request['user_type'] : 'client';
        
        $user_list = User::query();
        
        $user_list->when(request('user_type'), function ($q) use($user_type) {
            return $q->where('user_type', $user_type);
        });

        $user_list->when(request('country_id'), function ($q) {
            return $q->where('country_id', request('country_id'));
        });

        $user_list->when(request('city_id'), function ($q) {
            return $q->where('city_id', request('city_id'));
        });

        if( $request->has('status') && isset($request->status) )
        {
            $user_list = $user_list->where('status',request('status'));
        }
        
        if( $request->has('is_deleted') && isset($request->is_deleted) && $request->is_deleted){
            $user_list = $user_list->withTrashed();
        }


        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page))
        {
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $user_list->count();
            }
        }
        
        $user_list = $user_list->orderBy('id','desc')->paginate($per_page);

        $items = UserResource::collection($user_list);

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];
        
        return json_custom_response($response);
    }
    public function userFilterList(Request $request)
    {
        $params = $request->search;
        $user_list = User::where('name', 'LIKE', '%' . $params . '%');

        $user_type = $request->has('user_type') ? request('user_type') : 'client';
        
        $user_list->when(request('user_type'), function ($q) use($user_type) {
            return $q->where('user_type', $user_type);
        });

        if($request->has('is_deleted') && isset($request->is_deleted) && $request->is_deleted){
            $user_list = $user_list->withTrashed();
        }

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page))
        {
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page == -1 ){
                $per_page = $user_list->count();
            }
        }
        
        $user_list = $user_list->orderBy('id','desc')->paginate($per_page);

        $items = UserResource::collection($user_list);

        $response = [
            'pagination' => json_pagination_response($items),
            'data' => $items,
        ];
        
        return json_custom_response($response);
    }

    public function userDetail(Request $request)
    {
        $id = $request->id;

        $user = User::where('id',$id)->withTrashed()->first();
        if(empty($user))
        {
            $message = __('message.user_not_found');
            return json_message_response($message,400);   
        }

        $user_detail = new UserResource($user);

        $response = [
            'data' => $user_detail
        ];
        return json_custom_response($response);

    }

    public function commonUserDetail(Request $request)
    {
        $id = $request->id;

        $user = User::where('id',$id)->withTrashed()->first();
        if(empty($user))
        {
            $message = __('message.user_not_found');
            return json_message_response($message,400);   
        }

        $user_detail = new UserDetailResource($user);

        $wallet_history = $user->userWalletHistory()->orderBy('id','desc')->paginate(10);
        $wallet_history_items = WalletHistoryResource::collection($wallet_history);
        $response = [
            'data' => $user_detail,
            'wallet_history' => [
                'pagination' => json_pagination_response($wallet_history_items),
                'data'  => $wallet_history_items,
            ]
        ];
        if( $user->user_type == 'delivery_man' ) {
            $earning_detail = User::select('id','name')->withTrashed()->where('id', $user->id)
                ->with(['userWallet:total_amount,total_withdrawn', 'getPayment:order_id,delivery_man_commission,admin_commission'])
                ->withCount(['deliveryManOrder as total_order',
                    'getPayment as paid_order' => function ($query) {
                        $query->where('payment_status', 'paid');
                    }
                ])
                ->withSum('userWallet as wallet_balance', 'total_amount')
                ->withSum('userWallet as total_withdrawn', 'total_withdrawn')
                ->withSum('getPayment as delivery_man_commission', 'delivery_man_commission')
                ->withSum('getPayment as admin_commission', 'admin_commission')->first();

            $response['earning_detail'] = new DeliveryManEarningResource($earning_detail);

            $earning_list = Payment::with('order')->withTrashed()->where('payment_status','paid')
                ->whereHas('order',function ($query) use($user) {
                    $query->whereIn('status',['completed','cancelled'])->where('delivery_man_id', $user->id);
                })->orderBy('id','desc')->paginate(10);
            
            $earning_list_items = PaymentResource::collection($earning_list);
            $response['earning_list']['pagination'] = json_pagination_response($earning_list_items);
            $response['earning_list']['data'] = $earning_list_items;
        }

        return json_custom_response($response);
    }

    public function changePassword(Request $request){
        $user = User::where('id',\Auth::user()->id)->first();

        if($user == "") {
            $message = __('message.user_not_found');
            return json_message_response($message,400);   
        }
           
        $hashedPassword = $user->password;

        $match = Hash::check($request->old_password, $hashedPassword);

        $same_exits = Hash::check($request->new_password, $hashedPassword);
        if ($match)
        {
            if($same_exits){
                $message = __('message.old_new_pass_same');
                return json_message_response($message,400);
            }

			$user->fill([
                'password' => Hash::make($request->new_password)
            ])->save();
            
            $message = __('message.password_change');
            return json_message_response($message,200);
        }
        else
        {
            $message = __('message.valid_password');
            return json_message_response($message,400);
        }
    }

    public function updateProfile(UserUpdateRequest $request)
    {   
        $user = \Auth::user();
        if($request->has('id') && !empty($request->id)){
            $user = User::where('id',$request->id)->first();
        }
        if($user == null){
            return json_message_response(__('message.not_found_entry',['name' => __('message.client')]),400);
        }

        $user->fill($request->all())->update();

        if(isset($request->profile_image) && $request->profile_image != null ) {
            $user->clearMediaCollection('profile_image');
            $user->addMediaFromRequest('profile_image')->toMediaCollection('profile_image');
        }

        $user_data = User::find($user->id);
        if($user_data->userBankAccount != null && $request->has('user_bank_account')) {
            $user_data->userBankAccount->fill($request->user_bank_account)->update();
        } else if( $request->has('user_bank_account') && $request->user_bank_account != null ) {
            $user_data->userBankAccount()->create($request->user_bank_account);
        }
                
        $message = __('message.updated');
        // $user_data['profile_image'] = getSingleMedia($user_data,'profile_image',null);
        unset($user_data['media']);
        $response = [
            'data' => new UserResource($user_data),
            'message' => $message
        ];
        return json_custom_response( $response );
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if($request->is('api*')){

            $clear = request('clear');
            if( $clear != null ) {
                $user->$clear = null;
            }
            $user->save();
            return json_message_response('Logout successfully');
        }
    }

    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $response = Password::sendResetLink(
            $request->only('email')
        );

        return $response == Password::RESET_LINK_SENT
            ? response()->json(['message' => __($response), 'status' => true], 200)
            : response()->json(['message' => __($response), 'status' => false], 400);
    }
    
    public function socialLogin(Request $request)
    {
        $input = $request->all();

        $user_data = User::where('email',$input['email'])->first();

        if($input['login_type'] === 'mobile'){
            $user_data = User::where('username',$input['username'])->where('login_type','mobile')->first();
        }

        if( $user_data != null ) {
            if( !isset($user_data->login_type) || $user_data->login_type  == '' )
            {
                if(in_array($request->login_type, [ 'google', 'apple']))
                {
                    $message = __('validation.unique',['attribute' => 'email' ]);
                } else {
                    $message = __('validation.unique',['attribute' => 'username' ]);
                }
                return json_message_response($message,400);
            }

            if( $user_data->user_type != request('user_type') ) {
                if(in_array($request->login_type, [ 'google', 'apple'])) {
                    $message = __('validation.unique',['attribute' => 'email' ]);
                } else {
                    $message = __('validation.unique',['attribute' => 'username' ]);
                }
                return json_message_response($message,400);
            }
            $message = __('message.login_success');
        } else {

            if(in_array($request->login_type, [ 'google', 'apple']))
            {
                $key = 'email';
                $value = $request->email;
            } else {
                $key = 'username';
                $value = $request->username;
            }
            
            $trashed_user_data = User::where($key,$value)->whereNotNull('login_type')->withTrashed()->first();
            
            if ($trashed_user_data != null && $trashed_user_data->trashed())
            {
                if(in_array($request->login_type, [ 'google', 'apple'])){
                    $message = __('validation.unique',['attribute' => 'email' ]);
                } else {
                    $message = __('validation.unique',['attribute' => 'username' ]);
                }
                return json_message_response($message,400);
                
                if($request->login_type === 'mobile' && $user_data == null ){
                    $otp_response = [
                        'status' => true,
                        'is_user_exist' => false
                    ];
                    return json_custom_response($otp_response);
                }
            }

            $password = !empty($input['accessToken']) ? $input['accessToken'] : $input['email'];
            if(in_array($request->login_type, [ 'google', 'apple'])) {
                $input['email_verified_at'] = date('Y-m-d H:i:s');
            }
            
            $input['password'] = Hash::make($password);
            $input['user_type'] = isset($input['user_type']) ? $input['user_type'] : 'client';
            $user = User::create($input);
    
            $user_data = User::where('id',$user->id)->first();
            $message = __('message.save_form',['form' => $input['user_type'] ]);
        }
        $user_data['api_token'] = $user_data->createToken('auth_token')->plainTextToken;
        $user_data['profile_image'] = getSingleMedia($user_data,'profile_image',null);
        $response = [
            'status' => true,
            'message' => $message,
            'data' => $user_data
        ];
        return json_custom_response($response);
    }

    public function updateUserStatus(Request $request)
    {
        $user_id = $request->id;
        $user = User::where('id',$user_id)->first();

        if($user == "") {
            $message = __('message.user_not_found');
            return json_message_response($message,400);
        }
        if($request->has('status')) {
            $user->status = $request->status;
        }
        if($request->has('uid')) {
            $user->uid = $request->uid;
        }
        
        if($request->has('latitude')) {
            $user->latitude = $request->latitude;
        }
        
        if($request->has('longitude')) {
            $user->longitude = $request->longitude;
        }

        if($request->has('fcm_token')) {
            $user->fcm_token = $request->fcm_token;
        }

        if($request->has('country_id')) {
            $user->country_id = $request->country_id;
        }

        if($request->has('city_id')) {
            $user->city_id = $request->city_id;
        }
        if($request->has('player_id')) {
            $user->player_id = $request->player_id;
        }
        
        if($request->has('otp_verify_at')) {
            $user->otp_verify_at = $request->otp_verify_at;
        }
        
        $user->save();

        $message = __('message.update_form',['form' => __('message.status') ]);
        $response = [
            'data' => new UserResource($user),
            'message' => $message
        ];
        return json_custom_response($response);
    }

    public function updateAppSetting(Request $request)
    {
        $data = $request->all();
        AppSetting::updateOrCreate(['id' => $request->id],$data);
        $message = __('message.save_form',['form' => __('message.app_setting') ]);
        $response = [
            'data' => AppSetting::first(),
            'message' => $message
        ];
        return json_custom_response($response);
    }

    public function getAppSetting(Request $request)
    {
        if($request->has('id') && isset($request->id)){
            $data = AppSetting::where('id',$request->id)->first();
        } else {
            $data = AppSetting::first();
        }

        return json_custom_response($data);
    }

    public function deleteUser(Request $request)
    {
        $user = User::find($request->id);

        $message = __('message.msg_fail_to_delete',['item' => __('message.'.$user->user_type) ]);
        
        if( $user != '' ) {
            $user->delete();
            $message = __('message.msg_deleted',['name' => __('message.'.$user->user_type) ]);
        }
        
        if(request()->is('api/*')){
            return json_custom_response(['message'=> $message , 'status' => true]);
        }
    }

    public function userAction(Request $request)
    {
        $id = $request->id;
        $user = User::withTrashed()->where('id',$id)->first();
        
        $message = __('message.not_found_entry',['name' => __('message.'.$user->user_type) ]);
        if($request->type === 'restore'){
            $user->restore();
            $message = __('message.msg_restored',['name' => __('message.'.$user->user_type) ]);
        }

        if($request->type === 'forcedelete'){
            $user->forceDelete();
            $message = __('message.msg_forcedelete',['name' => __('message.'.$user->user_type) ]);
        }

        return json_custom_response(['message'=> $message, 'status' => true]);
    }

    public function verifyOTPForEmail(Request $request)
    {
        $user = auth()->user();
        $code = request('code');

        $verification = VerificationCode::where('user_id',$user->id)->where('code', $code)->first();

        if( $verification != null) {
            $diff_minute = calculateDuration($verification->datetime);
            
            if( $diff_minute >= 10){
                $message = __('message.otp_expire');
                $status = 400;
            } else {
                $message = __('message.otp_verified');
                $status = 200;
                $user->update(['email_verified_at' => date('Y-m-d H:i:s')]);
            }
            $verification->delete();
        } else {
            $message = __('message.otp_invalid');
            $status = 400;
        }
        return json_message_response($message,$status);
    }

    public function resendOTPForEmail()
    {
        $user = auth()->user();
        if($user->email_verified_at != null) {
            return json_message_response(__('message.email_is_verified'));
        }
        $user->notify(new EmailVerification($user));

        return json_message_response(__('message.otp_send'));
    }
    public function multipleDeleteRecords(Request $request)
    {
        $multi_ids = $request->ids;
        $user_type = $request->user_type != null ? $request->user_type : 'client';
        $message = __('message.msg_fail_to_delete', ['item' => __('message.'.$user_type)]);

        foreach ($multi_ids as $id) {
            $user = User::withTrashed()->where('id',$id)->first();
            if ($user) {
                if( $user->deleted_at != null ) {
                    $user->forceDelete();
                } else {                        
                    $user->delete();
                }
                $message = __('message.msg_deleted',['name' => __('message.'.$user->user_type) ]);
            }
        }

        return json_custom_response(['message'=> $message , 'status' => true]);
    }
}
