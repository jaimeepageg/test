<?php

namespace Ceremonies\Controllers;

use Carbon\Carbon;
use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\Package;
use Ceremonies\Repositories\PackagesRepository;

class PackageController
{

    private $repository;

    /**
     * Set up the repository
     */
    public function __construct()
    {
        $this->repository = Bootstrap::container()->get(PackagesRepository::class);
    }

    public function index() {
        $packages = Package::orderBy('id', 'DESC')->get();
        $packages = $packages->map(function($package){
            $package->loadUser();
            return [
                'id' => $package->id,
                'name' => $package->user->display_name ?? 'N/A',
                'type' => ucfirst($package->type),
                'package' => $package->name,
                'start_date' => $package->getStartDate(),
                'renewal_date' => $package->getExpiryDate(),
                'status' => $package->getStatus()
            ];
        });
        return new \WP_Rest_Response($packages);
    }
    
    public function single(\WP_Rest_Request $request) {
        $package = Package::where('id', $request->get_param('id'))
            ->with('payments')->first();
        $package->loadUser();
        $package->loadListing();
        return new \WP_Rest_Response($package);
    }

    /**
     * Sends out six and one week renewal reminders, returns no
     * response as run as a CRON job.
     *
     * @return void
     */
    public function renewalReminders()
    {

        // Check all packages that are 6 weeks away that have no 'lastReminderSent'
        // Then check all packages that are 1 week away that has had a reminder sent
        // Make sure the reminder doesn't re-run if the lastReminderSent is there and less
        // than a week old
        $this->repository->sendSixWeekReminders();
        $this->repository->sendOneWeekReminders();

        exit();

    }

    /**
     * Checks for expired advertisement packages and hides any
     * expired posts. Returns no respones as run as a CRON job.
     *
     * @return void
     */
    public function expiredCheck()
    {
        $this->repository->hideExpiredPackageListings();
        exit();
    }

    /**
     * Handler to manually send a package renewal reminder
     * email.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function sendRenewal(\WP_REST_Request $request)
    {
        $package = Package::where('id', $request->get_param('id'))->first();
        $this->repository->sendSingleReminder($package);
        return new \WP_REST_Response(['success' => true]);
    }
    
}