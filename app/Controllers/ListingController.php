<?php

namespace Ceremonies\Controllers;

use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\Package;
use Ceremonies\Repositories\ListingsRepository;
use Ceremonies\Models\Listings\Supplier;
use Ceremonies\Models\Listings\Venue;
use Ceremonies\Services\Mail;

class ListingController
{

    private $repository;

    /**
     * Set up the repository
     */
    public function __construct()
    {
        $this->repository = Bootstrap::container()->get(ListingsRepository::class);
    }

    public function store()
    {

        $request = sanitize_array($_POST);
        $package = Package::where('id', $request['package_id'])->first();

//        if ($package->post_id) {
//            throw new \Exception('Listing has already been created for this account/package');
//        }

        /**
         * @var Venue|Supplier $listing
         */
        $listingClass = 'Ceremonies\\Models\\Listings\\' . ucfirst($package->type);
        $listing = new $listingClass;

        try {
            $post = $listing->createListing($request, $package);
            $listing->saveListingCustomFields($request, $post);
            // FIXME: Something going wrong here 
            $listing->saveListingImages($post);
            $listing->saveListingTerms($request, $post);
        } catch (\Exception $e) {
            return new \WP_REST_Response($e->getMessage(), 500);
        }

        // Attach to package
        $package->post_id = $post;
        $package->save();
		$package->loadUser();

        // Send notification email to admin
	    Mail::create('Supplier Listing Submitted')
//	        ->sendTo('ceremonysupport@staffordshire.gov.uk');
            ->sendTo('dev-team@wethrive.agency')
	        ->with($package->toArray())
	        ->send();

        // Send response
        return new \WP_REST_Response(['success' => true, 'redirect' => '/dashboard/?submitted=true']);

    }

    /**
     * Takes a $name and checks if a listing already exists.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function nameCheck(\WP_REST_Request $request)
    {

        if (!$request->get_param('name')) {
            throw new \Exception('No name provided');
        }

        $listing = $this->repository->findListingByName($request->get_param('name'));

        $response = array(
            'success' => true,
            'found' => (bool) $listing, // Make sure to return boolean
        );

        return new \WP_REST_Response($response);
    }

}