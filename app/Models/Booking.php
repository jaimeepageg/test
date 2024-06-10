<?php

namespace Ceremonies\Models;

use Carbon\Carbon;
use Ceremonies\Core\Bootstrap;
use Ceremonies\Core\Model;
use Ceremonies\Models\Bookings\Choices;
use Ceremonies\Models\Bookings\Notes;
use Ceremonies\Services\Token;
use Ceremonies\Services\Zipporah\Zipporah;

class Booking extends Model
{

    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_COMPLETED = 'Complete';
    public const STATUS_PENDING = 'Pending Approval';
    public const STATUS_APPROVED = 'Approved';
    public const STATUS_REVIEW = 'Review';


    /**
     * How many hours should pass before data is
     * invalid.
     */
    private int $cacheTimeout = 1;

    /**
     * Types of ceremony available within Zipporah. The
     * booking type ID matches this array order.
     *
     * @var string[]
     */
    public $types = [
        'Birth',
        'Birth Re-registration',
        'Death',
        'Notice Of Marriage',
        'Notice Of Civil Partnership',
        'Marriage Ceremony',
        'Civil Partnership Ceremony',
        'Naming Ceremony',
        'Renewal Of Vows Ceremony',
        'NCS',
        'Quick'
    ];

    public static $invalidTypes = [
        'Birth',
        'Birth Re-registration',
        'Death',
        'NCS',
        'Quick'
    ];

    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
        'updated_at' => 'datetime:d/m/Y H:i',
        'booking_date' => 'datetime:l jS \\of F Y \\a\\t h:iA',
    ];

    protected $guarded = ['id'];

    // Relationships
    public function token()
    {
        return $this->hasOne(Token::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function notes()
    {
        return $this->hasMany(Notes::class);
    }

    public function choices()
    {
        return $this->hasOne(Choices::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public function payments()
    {
        return $this->hasMany(BookingPayment::class);
    }

    // Methods

    /**
     * Check if the booking has completed its
     * initial pull of data from Zipporah.
     *
     * @return bool
     */
    public function hasInitialised()
    {
        return $this->zip_last_pull !== null;
    }

    /**
     * Check if the local data is stale and
     * needs revalidating.
     *
     * @return bool
     */
    public function isCacheStale()
    {
        $hoursPassed = Carbon::now()->diffInHours(Carbon::parse($this->zip_last_pull));
        return $hoursPassed >= $this->cacheTimeout;
    }

    public function updateCacheTime()
    {
        $this->zip_last_pull = Carbon::now();
        $this->save();
    }

    /**
     * Updates the zip_last_pull property.
     *
     * @return void
     */
    public function updateLastPull()
    {
        $this->zip_last_pull = Carbon::now()->toDateTimeString();
    }

    public function getBookingDate()
    {
        return Carbon::parse($this->booking_date)->format('l jS \\of F Y \\a\\t h:ia');
    }

    public function getBookingDateForFile()
    {
        return Carbon::parse($this->booking_date)->format('d-m-Y');
    }

    /**
     * Checks if the ceremony has passed.
     *
     * @return bool
     */
    public function hasCeremonyPast()
    {
        return Carbon::parse($this->booking_date)->startOfDay()->isPast();
    }

    public function getUpdatedAt()
    {
        return Carbon::parse($this->updated_at)->format('d/m/y H:i');
    }

    /**
     * Updates a booking with fresh data from
     * Zipporah.
     *
     * @param $data
     * @return void
     */
    public function populate($data)
    {
        $zipporah = Bootstrap::container()->get(Zipporah::class);
        $this->booking_date = Carbon::parse($data->startTime);
        $this->zip_related_bookings = $data->multipleBookingId;
        $this->office = $zipporah->removeVenuePrefix($data->resourceCategoryName);
        $this->location = $data->venueName;
        $this->type = $data->bookingTypeName;
        $this->raw_data = json_encode($data);
        $this->status = self::STATUS_IN_PROGRESS;
        $this->updateLastPull();
        $this->save();
    }

    public function getPostcode()
    {
        return $this->clients->where('is_primary', true)->first()->postcode ?? '';
    }

    /**
     * Gets the booking URL for a notice of marriage ceremony.
     *
     * @return string
     */
    public function getBookingNoticeUrl(): string
    {
        return 'https://staffordshire.zipporah.co.uk/Registrars.Staffordshire.Sandpit/NoticeOfMarriageBookingProcess/IndexFromCeremony?bookingId=' . $this->zip_reference;
    }

    public static function getTokenBooking()
    {
        $reference = Token::getTokenName();
        return self::where('zip_reference', $reference)->first();
    }

    public function getClientNames()

    {
        $names = $this->clients->map(function ($client) {
            return $client->first_name . ' ' . $client->last_name;
        });
        return implode(" and ", $names->toArray());
    }

    public function getPrimaryClient()
    {
        return $this->clients->firstWhere('is_primary', true);
    }

    /**
     * Add a Note to the booking.
     *
     * @param $message
     * @return void
     */
    public function addNote($message)
    {
        $this->notes()->create([
            'message' => $message,
            'created_at' => Carbon::now(),
            //  'user_id' => get_current_user_id(), TODO: Figure out how to get current user ID in stateless env
        ]);
    }

    public function getContactName()
    {
        return $this->choices->questions->firstWhere('name', 'contact_name')->answer;
    }

    public function getContactPhone()
    {
        return $this->choices->questions->firstWhere('name', 'mobile_telephone')->answer;
    }

    public function getContactEmail()
    {
        return $this->choices->questions->firstWhere('name', 'contact_email_address')->answer;
    }

    public function getCoupleType()
    {
        return $this->choices->questions->firstWhere('name', 'who_is_getting_married')->answer ?? '';
    }

    public function getBalance()
    {
        return number_format($this->payments->where('status', 'PaymentRequired')->sum('amount'), 2);
    }

    public function getPaymentTask()
    {
        return $this->tasks->where('name', 'Pay Balance')->first();
    }

    /**
     * Gets the payment reference from the first complete
     * payment. There should only ever be one complete payment.
     *
     * @return null
     */
    public function getPaymentReference()
    {
        // Must be an App\Models\Payment not a App\Models\BookingPayment
        $payment = Payment::where('bookingId', $this->id)->where('state', 'COMPLETE')->first();

        if (!$payment) {
            return null;
        }

        return $payment->transactionId;
    }

    /**
     * Check if a booking has already run its initial
     * pull of data from Zipporah.
     *
     * @return bool
     */
    public function isInitialised()
    {
        // Booking should have clients and tasks associated with the booking.
        return $this->clients->count() > 0 && $this->tasks->count() > 0;
    }

    /**
     * Marks the booking as 'In Progress', starting the
     * choices form is the primary trigger for this.
     *
     * @return void
     */
    public function markInProgress()
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
            $this->status = self::STATUS_IN_PROGRESS;
            $this->save();
        }
    }

    /**
     * Marks the booking as 'Review', submitting the
     * choices form is the primary trigger for this.
     *
     * @return void
     */
    public function markForReview()
    {
        if ($this->status !== self::STATUS_PENDING) {
            $this->status = self::STATUS_PENDING;
            $this->save();
        }
    }

    public function markApproved()
    {
        if ($this->status !== self::STATUS_APPROVED) {
            $this->status = self::STATUS_APPROVED;
            $this->save();
        }
    }

    public function getClientPostcodes()
    {
        return $this->clients->map(function (Client $client) {
            if ($client->postcode !== '') {
                return $client->postcode;
            }
        });
    }

    /**
     * Gets the location type for a booking,
     * can be either an Approved Venue or
     * Registration Office.
     *
     * @return string
     */
    public function getLocationType() : string
    {
        return str_contains($this->type, 'RO') ? 'registration_office' : 'approved_venue';
    }

    public function getRegOfficeEmail()
    {
        return get_field(strtolower($this->office), 'options');
    }

    /**
     * Check if a booking already exists. Use before
     * creating to avoid MySQL fatal error.
     *
     * @param $zipporahBooking
     * @return mixed
     */
    public static function alreadyExists($zipporahBooking)
    {
        return self::where('zip_reference', $zipporahBooking->bookingId)->exists();
    }

}
