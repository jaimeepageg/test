<?php

namespace Ceremonies\Models\Listings;

interface ListingInterface
{

    public function createListing($data, $package) : int;

    public function saveListingCustomFields($data, $post) : void;

    public function saveListingImages($post) : void;

    public function saveListingTerms($data, $post) : void;
}