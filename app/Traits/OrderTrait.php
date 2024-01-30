<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\AppSetting;
use Twilio\Rest\Client;

trait OrderTrait {

    public function autoAssignOrder($order_data,$request_data = null)
    {
        $latitude = $order_data->pickup_point['latitude'];
        $longitude = $order_data->pickup_point['longitude'];
        $app_setting = AppSetting::first();
        $unit = isset($app_setting->distance_unit) ? $app_setting->distance_unit : 'km';
        $radius = isset($app_setting->distance) ? $app_setting->distance : 50;
        $unit_value = convertUnitvalue($unit);
        $nearby_deliveryperson = User::selectRaw("id, user_type, latitude, longitude, ( $unit_value * acos( cos( radians($latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($longitude) ) + sin( radians($latitude) ) * sin( radians( latitude ) ) ) ) AS distance")
                                ->where('city_id',$order_data->city_id)->where('status', 1)
                                ->where('user_type', 'delivery_man')
                                ->having('distance', '<=', $radius)
                                ->orderBy('distance','asc');
        
        $nearby_deliveryperson = $nearby_deliveryperson->when(request('cancelled_delivery_man_ids'), function ($q) {
            return $q->whereNotIn('id', request('cancelled_delivery_man_ids'));
        })->first();
        if(request('cancelled_delivery_man_ids') != null) {
            $history_data = [
                'history_type' => 'courier_auto_assign_cancelled',
                'order_id' => $order_data->id,
                'order' => $order_data,
            ];
            saveOrderHistory($history_data);
        }
        // dd($nearby_deliveryperson);
        if( $nearby_deliveryperson != null )
        {
            $data = [
                'auto_assign' => 1,
                'cancelled_delivery_man_ids' => array_key_exists('cancelled_delivery_man_ids',$request_data) ? $request_data['cancelled_delivery_man_ids'] : null,
                'delivery_man_id' => $nearby_deliveryperson->id,
                'status' => 'courier_assigned',
            ];
            $order_data->fill($data)->update();

            $history_data = [
                'history_type' => 'courier_assigned',
                'order_id' => $order_data->id,
                'order' => $order_data,
            ];
            
            saveOrderHistory($history_data);
        } else {
            $data = [
                'status' => 'create',
                'auto_assign' => 0,
                'cancelled_delivery_man_ids' => array_key_exists('cancelled_delivery_man_ids',$request_data) ? $request_data['cancelled_delivery_man_ids'] : null,
                'delivery_man_id' => null,
            ];
        }
        $order_data->fill($data)->update();
        return $order_data;
    }

    public function sendTwilioSMS($order)
    {
        $is_twilio_sms = (bool) SettingData('twilio', 'is_twilio_sms') ?? false;

        $sid    = env('TWILIO_ACCOUNT_SID');
        $token  = env('TWILIO_AUTH_TOKEN');
        $sms_from = env('TWILIO_FROM');
        $whatsapp_from = env('TWILIO_WHATSAPP_FROM');

        if ( in_array(null, [ $sid, $token ])) {
            return;
        }
        
        $twilio = new Client($sid, $token);
        $pickup_number = $order->pickup_point['contact_number'];

        $message = __('message.twilio_order.client_create', ['id' => $order->id]);
        if( $is_twilio_sms && $sms_from != null ) {
            $twilio->messages->create(
                $pickup_number,
                array(
                    'from' => $sms_from,
                    'body' => $message
                )
            );
        }

        $is_twilio_whatsapp = (bool) SettingData('twilio', 'is_twilio_whatsapp') ?? false;

        if( $is_twilio_whatsapp && $whatsapp_from != null ) {
            $twilio->messages->create(
                        'whatsapp:'.$pickup_number,
                        [
                            'from' => 'whatsapp:'.$whatsapp_from,
                            'body' => $message,
                        ]
            );
        }
    }
}