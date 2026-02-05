<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    protected $fillable = [
    'client_id',
    'order_number',
    'order_date',
    'customer_name',
    'business_name',
    'mobile',
    'email',
    'package_name',
    'leads_count',
    'cost_per_lead',
    'expired_from',
    'expired_on',
    'selectproofid',
    'proofid',
    'paid_amount',
    'coins_amt',
    'gst_status',
    'gst_tax',
    'gst_total_amount',
    'tds_status',
    'tds_amount',
    'total_amount',
    'paid_amt_in_words',
    'payment_mode',
    'pay_mode_details',
    'transactionid',
    'currency',
    'payment_bank',
    'chq_card_no',
    'bank_card_no',
    'pay_paytm',
    'pay_neft',
    'pay_googlePay',
    'pay_bank',
    'payment_updatedBy',
    'paymentcollect',
    'invoice_status',
    'status',
    'payment_link_id',
    'payment_url',
];

}
