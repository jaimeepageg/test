<?php

namespace Ceremonies\Middleware;

use Ceremonies\Services\Token;

class Auth {

	/**
	 * Loads the middleware
	 *
	 * @return void
	 */
	public function load(): void {

		$header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
		if (!$header) {
			$this->unauthResponse('Authorisation token is missing from request.');
		}

		$matches = array();
		if (!preg_match('/Bearer\s(\S+)/', $header, $matches)) {
			$this->unauthResponse('Authorisation token is invalid or malformed.');
		}

		// Get JWT from header
		$this->checkToken($matches[1]);

	}

	/**
	 * Check the JWT is valid.
	 *
	 * @param $jwt
	 * @return void
	 */
	private function checkToken($jwt): void {
		// Check token is real and has not expired.
		if (!Token::validate($jwt)) {
			$this->unauthResponse('Authorisation token is invalid.');
		}
	}

	/**
	 * Send an unauthorised response and exit the script
	 * early.
	 *
	 * #[NoReturn]
	 * @param $message
	 * @return void
	 */
	private function unauthResponse($message) {
		http_response_code(401);
		echo json_encode(['message' => $message, 'status' => 401]);
		exit();
	}

}