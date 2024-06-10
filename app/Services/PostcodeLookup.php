<?php

namespace Ceremonies\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class PostcodeLookup {


	/**
	 * Find data attached to a given postcode
	 *
	 * @param string $postcode
	 * @return mixed
	 * @throws GuzzleException
	 */
	public static function find(string $postcode) {

		$postcodeData = self::getTransient($postcode);
		if ($postcodeData) {
			return $postcodeData;
		}

		// setup guzzle client and make request api.postcodes.io/postcodes/{postcode}
		try {
			$response = (new Client())->request('GET', 'https://api.postcodes.io/postcodes/' . $postcode);
		} catch (GuzzleException $e) {
			return false;
		}

		// Get result from response
		$data = json_decode($response->getBody(), true);

		// Set transient before returning
		self::setTransient($postcode, $data['result']);

		return $data['result'];

	}

	/**
	 * Get cached data for a postcode.
	 *
	 * @param string $postcode
	 * @return void
	 */
	private static function getTransient(string $postcode) {
		$transient = get_transient('sc_postcode_' . $postcode);
		if ($transient) {
			return $transient;
		}
	}

	/**
	 * Cache data for a postcode.
	 *
	 * @param string $postcode
	 * @param array $data
	 * @return void
	 */
	private static function setTransient(string $postcode, array $data) {
		set_transient('sc_postcode_' . $postcode, $data, MONTH_IN_SECONDS * 3);
	}

	/**
	 * Checks if a postcode is within the Staffordshire council
	 * catchment area.
	 *
	 * @return bool
	 */
	public static function isAllowed( string $postcode): bool
    {

        if ($postcode === '') {
            return false;
        }

		$postcodeData = self::find($postcode);

        return in_array($postcodeData['primary_care_trust'], ['North Staffordshire', 'South Staffordshire']);
	}

}