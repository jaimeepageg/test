<?php

namespace Ceremonies\Controllers;

class FaqController {

	/**
	 * Fetches all the FAQ's from WordPress with the acf field
	 * 'show_in_portal' as true.
	 *
	 * @return \WP_REST_Response
	 */
	public function index(\WP_REST_Request $request) {

		$args = array(
			'post_type' => 'faq',
			'posts_per_page' => -1,
			'meta_query' => array(
				array(
					'key' => 'show_in_portal',
					'value' => true,
				)
			)
		);

		if ($request->has_param('category')) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'faq_category',
					'field' => 'slug',
					'terms' => $request->get_param('category')
				)
			);
		}

		$faqs = get_posts($args);
		return new \WP_REST_Response(['success' => true, 'data' => $faqs]);
	}

}
