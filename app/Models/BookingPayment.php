<?php

namespace Ceremonies\Models;

use Ceremonies\Core\Bootstrap;
use Ceremonies\Core\Model;
use Ceremonies\Services\Zipporah\ZipporahV2;

class BookingPayment extends Model
{

    protected $fillable = ['zip_id', 'item_name', 'amount', 'status'];
    public $timestamps = false;

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id', 'id');
    }

    public function getStatus()
    {
        return match ($this->status) {
            'PaymentRequired' => 'Yet to pay',
            'Success' => 'Paid',
            default => $this->status,
        };
    }

    /**
     * Check if the payment has been paid.
     *
     * @return bool
     */
    public function isPaid()
    {
        return $this->status === 'Success';
    }

    public function toArray()
    {
        return [
            'item_name' => $this->item_name,
            'status' => $this->getStatus(),
            'amount' => $this->amount,
        ];
    }

    public function markAsPaid()
    {
        if ($this->status !== 'Success') {
            $zip = Bootstrap::container()->get(ZipporahV2::class);
            $response = $zip->completePayment(
                $this->zip_id,
                $this->booking->getPaymentReference()
            );
//            print('<pre>Response: '.print_r($response, true).'</pre>');
//            dd($response);
            $this->status = 'Success';
            $this->save();
        }
    }

}