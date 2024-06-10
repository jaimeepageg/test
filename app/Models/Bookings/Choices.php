<?php

namespace Ceremonies\Models\Bookings;

use Ceremonies\Core\Model;
use Ceremonies\Models\Booking;

class Choices extends Model {

    public const STATUS_IN_PROGRESS = 'InProgress';
    public const STATUS_SUBMITTED = 'Submitted';
    public const STATUS_APPROVED = 'Approved';

	protected $casts = [
		'updated_at' => 'datetime: H:i d-m-Y',
	];

	/**
	 * Relationships
	 */

	public function booking() {
		return $this->belongsTo(Booking::class);
	}

	public function questions() {
		return $this->hasMany(ChoicesQuestion::class, 'form_id', 'id');
	}

    public function files()
    {
        return $this->hasMany(ChoicesFile::class, 'form_id', 'id');
    }

	/**
	 * Methods
	 */
	public function markSubmitted(): void {
		$this->status = 'Submitted';
		$this->save();
	}

	public function markApproved() {
		$this->status = 'Approved';
		$this->save();
	}

	public function markDenied(): void {
		$this->status = 'Denied';
		$this->save();
	}

	public function getFilteredQuestions() {
		$blacklist = ['contact_name', 'mobile_telephone', 'contact_email_address', 'who_is_getting_married'];
		return $this->questions->whereNotIn('name', $blacklist)->all();
	}

    /**
     * Checks if the form has been submitted, returns
     * true for submitted and approved.
     *
     * @return bool
     */
    public function isSubmitted()
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_APPROVED]);
    }

    /**
     * Checks if the form is in progress.
     *
     * @return bool
     */
    public function isInProgress()
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Get a user-friendly version of the form name.
     *
     * @return string
     */
    public function getFormName()
    {
        return match($this->form_name) {
            'cpEnhancedCeremony' => 'Civil Ceremony Enhanced Choices',
            'cpTraditionalCeremony' => 'Civil Partnership Ceremony Choices',
            'traditionalCeremony' => 'Marriage Ceremony Choices',
            'enhancedCeremony' => 'Marriage Ceremony Enhanced Choices',
            'babyNaming' => 'Naming Ceremony Choices',
            'statutoryCeremony' => 'Statutory Ceremony Choices',
            'vowRenewalCeremony' => 'Vow Renewal Ceremony Choices',
            default => 'Ceremony Form',
        };
    }

    /**
     * Get a user-friendly version of the Ceremony Type
     *
     * @return string
     */
    public function getCeremonyTypeName()
    {
        return match($this->form_name) {
            'cpEnhancedCeremony' => 'Enhanced Civil Partnership Ceremony',
            'cpTraditionalCeremony' => 'Traditional Civil Partnership Ceremony',
            'traditionalCeremony' => 'Traditional Marriage Ceremony',
            'enhancedCeremony' => 'Enhanced Marriage Ceremony',
            'babyNaming' => 'Naming Ceremony',
            'statutoryCeremony' => 'Statutory Ceremony',
            'vowRenewalCeremony' => 'Vow Renewal Ceremony',
            default => 'Ceremony Form',
        };
    }

    /**
     * @return array
     */
    public function getSubmittedNames()
    {
        $partnerOne = $this->questions->where('name', 'partner_one_name')->first();
        $partnerTwo = $this->questions->where('name', 'partner_two_name')->first();
        return [$partnerOne->answer, $partnerTwo->answer];
    }

    /**
     * 'Unlocks' the form by setting the status
     * to 'InProgress'.
     *
     * @return void
     */
    public function unlock()
    {
        $this->status = 'InProgress';
        $this->save();
    }

}
