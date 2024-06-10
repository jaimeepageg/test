<?php

namespace Ceremonies\Repositories;

class ListingsRepository
{

    /**
     * Find a listing by an exact name match.
     *
     * @param $name
     * @return array
     */
    public function findListingByName($name)
    {
        global $wpdb;

        // Used manual query over get_posts as it was returning unpredictable
        // results.
        return $wpdb->get_row(
            $wpdb->prepare('SELECT * FROM wp_posts WHERE wp_posts.post_title = %s', $name),
            ARRAY_A
        );

    }


}