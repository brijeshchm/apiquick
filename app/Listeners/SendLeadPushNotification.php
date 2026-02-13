<?php

namespace App\Listeners;

use App\Events\LeadPush;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Services\FirebasePushService;
use App\Models\Client\Client;
use DB;
class SendLeadPushNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LeadPush $event): void
    {
  
    
        $lead = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->select('leads.*','assigned_leads.*', 'assigned_leads.client_id', 'assigned_leads.lead_id', 'assigned_leads.created_at as created', 'assigned_leads.id as assignId')
			->orderBy('assigned_leads.created_at', 'desc')
			->where('assigned_leads.client_id', $event->client_id)->where('leads.id', $event->lead->id)->first();
        // 🔹 Who should get notification?
        $users = Client::where('id',$event->client_id)->first();
 
      
        $title = ($lead->kw_text ?? 'Lead') . ' QuickDials.com';
        $body = trim((!empty($lead->name) ? $lead->name.' | ' : '').(!empty($lead->kw_text) ? $lead->kw_text.' | ' : '').(!empty($lead->mobile) ? $lead->mobile : ''), ' ');
 

      $response =   FirebasePushService::sendMultiple($users->fcm_token, $title, $body, [
            'lead_id'   => (string) ($lead->lead_id ?? ''),
            'assignId'  => (string) ($lead->assignId ?? ''),
            'name'      => (string) ($lead->name ?? ''),
            'mobile'    => (string) ($lead->mobile ?? ''),
            'email'     => (string) ($lead->email ?? ''),
            'cityName'  => (string) ($lead->city_name ?? ''),
            'area'      => (string) ($lead->zone ?? ''),
            'kw_text'   => (string) ($lead->kw_text ?? ''),			 
        ]);
 
        if ($response) {
        echo "true";
        } else {
               echo "false";
        }
        


 
    }
}
