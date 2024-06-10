<?php

namespace Ceremonies\Models\Listings;

use Ceremonies\Traits\HandleFilesTrait;

class Venue implements ListingInterface
{

    use HandleFilesTrait;

    public function createListing($data, $package) : int
    {

        // Insert post
        $post = wp_insert_post([
            'post_author' => $data['user_id'],
            'post_title' => $data['name'],
            'post_type' => $package->type,
            'post_status' => 'pending',
        ]);

        // Check if post was inserted
        if (!is_int($post)) {
            throw new \Exception($post->get_error_message());
        }

        return $post;

    }

    public function saveListingCustomFields($data, $post) : void
    {
        $customFields = array(
            'listing_tier' => $data['package_type'],
            'name' => $data['name'] ?? '',
            'subtitle' => $data['teaser'] ?? '',
            'content' => $data['description'] ?? '',
            'aside_content' => $data['aside_content'] ?? '',
            'address' => $data['address'],
            'phone_number' => $data['phone'],
            'email_address' => $data['email'],
            'website_link' => $data['website_url'],
            'social_choice' => isset($data['social_url']) && $data['social_url'] !== '' ? $data['social'] : '',
            'social_url' => $data['social_url'] ?? '',
            'facility_options' => $data['facilities'],
            'instagram_url' => $data['instagram_url'] ?? '',
            'facebook_url' => $data['facebook_url'] ?? '',
            'twitter_url' => $data['twitter_url'] ?? '',
            'pinterest_url' => $data['pinterest_url'] ?? '',
            'youtube_url' => $data['youtube_url'] ?? ''
        );
        foreach ($customFields as $key => $value) {
            update_field($key, $value, $post);
        }

    }

    public function saveListingImages($post) : void
    {

        // Pull the files out of the request
        $files = $this->getRequestFiles();

        // Main and Featured to be the same for now
        $featuredId = $this->addFileToWordPress($files['featured_image']);
        update_field('featured_image', $featuredId, $post);
        update_field('main_image', $featuredId, $post);

        // Use array of attachments ID's for gallery
        $galleryIds = array();
        foreach($files['gallery'] as $image) {
            $imageId = $this->addFileToWordPress($image);
            $galleryIds[] = $imageId;
        }
        update_field('image_gallery', $galleryIds, $post);

    }

    public function saveListingTerms($data, $post) : void
    {
        $taxonomies = array('size', 'type', 'style');
        foreach ($taxonomies as $tax) {
            wp_set_post_terms($post, $data[$tax], $tax);
        }
    }
}