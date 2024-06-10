<?php

namespace Ceremonies\Repositories;

use Carbon\Carbon;
use Ceremonies\Models\Package;
use Ceremonies\Services\Logger;
use Ceremonies\Services\Mail;

class PackagesRepository
{

    public function sendSixWeekReminders()
    {

        $packages = Package::where([
            ['lastReminderSent', null],
            ['expiryDate', '<', Carbon::now()->addWeeks(6)->toDateTimeString()]
        ])->get();

        $packages->map(function($package) {
            $this->sendReminders($package);
        });

    }

    public function sendOneWeekReminders()
    {
        $packages = Package::where([
            // Check between now and one week from now
            ['expiryDate', '<=', Carbon::now()->addWeeks()->toDateTimeString(), 'and'],
            ['expiryDate', '>=', Carbon::now()->toDateTimeString(), 'and'],
            // Last reminder sent to check between now and six weeks ago
            ['lastReminderSent', '<=', Carbon::now()->subWeeks()->toDateTimeString(), 'and']
        ])->toSql();

        $packages->map(function($package) {
            $this->sendReminders($package);
        });
    }

    private function sendReminders($package)
    {

        $package->loadUser();
        $package->loadListing();

        $mailable = Mail::create('Package Renewal')
            ->with($package->toArray())
            ->sendTo($package->user->user_email)
            ->send();

        if (!$mailable->sent) {
            Logger::log('Package renewal email failed to send', $package->toArray());
            return;
        }

        $package->lastReminderSent = date('Y-m-d H:i:s');

        // NOTE: Need to unset user and post - Issues with saving model
        unset($package->user);
        unset($package->post);

        $package->save();

    }

    /**
     * Hides the posts of any expired packages in
     * the admin panel.
     *
     * @return void
     */
    public function hideExpiredPackageListings()
    {

        $now = Carbon::now();
        $packages = Package::where('expiryDate', '<', $now)->get();
        $packages->map(function($package) {
            wp_update_post([
                'ID' => $package->post_id,
                'post_status' => 'pending',
            ]);
            $this->sendPackageExpiredEmail($package);
        });

    }

    private function sendPackageExpiredEmail(Package $package): void
    {
        if ($package->hasExpired() && $package->expiryNoticeSent === null) {

            // Send notice to user
            $mailable = Mail::create('Package Expired')
                ->with($package->toArray())
                ->sendTo($package->user->user_email)
                ->send();

            if ($mailable->sent) {
                $package->expiryNoticeSent = Carbon::now()->format('H:i:s - d/m/Y');
                $package->save();
            }

            // Send notice to admin
            Mail::create('Package Expired Notice')
                ->with($package->toArray())
                ->sendTo('c.underhill@wethrive.agency')
                ->send();

        }
    }

    /**
     * Sends a single package renewal reminder email.
     *
     * @param Package $package
     * @return void
     */
    public function sendSingleReminder(Package $package)
    {
        $this->sendReminders($package);
    }

}