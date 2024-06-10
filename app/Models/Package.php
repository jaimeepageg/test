<?php

namespace Ceremonies\Models;

use Ceremonies\Core\Model;
use Ceremonies\Observers\PackageObserver;
use Illuminate\Support\Carbon;

class Package extends Model
{

    protected $fillable = ['name', 'user_id', 'total', 'type'];
    public $timestamps = false;

    /**
     * Auto formats dates to be the correct
     * format.
     *
     * @var string[]
     */
    protected $casts = [
        'startDate' => 'datetime:H:i:s - d/m/Y',
        'expiryDate' => 'datetime:H:i:s - d/m/Y',
        'lastReminderSent' => 'datetime:H:i:s - d/m/Y',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class, 'packageId')->orderBy('id', 'DESC');
    }

    public function loadUser() {
        $this->user = get_user_by('id', $this->user_id);
    }

    public function loadListing() {
        $post = get_post($this->post_id, 'ARRAY_A');
        $post['fields'] = get_fields($this->post_id);
        $this->post = $post;
    }

    public function getExpiryDate()
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $this->expiryDate);
        return $date->format('d/m/Y');
    }

    public function getStartDate()
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $this->startDate);
        return $date->format('H:i d/m/Y');
    }

    public function hasExpired()
    {
        $now = strtotime(date('c'));
        $expiry = strtotime($this->expiryDate);
        return $now > $expiry;
    }

    public function getExpiredAgo()
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $this->expiryDate);
        return $date->diffForHumans();
    }

    /**
     * Get the current status of a package.
     *
     * @return string
     */
    public function getStatus()
    {
        if ($this->hasExpired()) {
            return 'Expired';
        } else if ($this->isPublished()) {
            return 'Active';
        } else if ($this->post_id) {
            return 'Pending Approval';
        } else {
            return 'Listing information required';
        }
    }

    public function isPublished()
    {
        return $this->post_id && get_post_status($this->post_id) === 'publish';
    }

    public function getListingUrl()
    {

        if ($this->listingUrl) {
            return $this->listingUrl;
        }

        $this->listingUrl = get_the_permalink($this->post_id);

        return $this->listingUrl;

    }

    /**
     * Checks if the package can be renewed, must be within 1 month
     * of expiry date.
     *
     * @return bool
     */
    public function canBeRenewed()
    {
        $now = Carbon::now();
        $difference = $this->expiryDate->diffInWeeks($now);
        return $difference <= 6;
    }

    public function getNewestPayment()
    {
        return $this->payments->sortByDesc('updated_at')->first();
    }

}