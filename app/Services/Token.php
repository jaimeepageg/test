<?php

namespace Ceremonies\Services;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token {

	/**
	 * JWT secret.
	 * @var string
	 */
	private static string $secret = "%<?p.mNOl7eZgL_cuW~&!`f_%k8W}r)-Y?ias'5[@0NlF~9nepn~X.-e";

	/**
	 * JWT subject.
	 * @var string
	 */
	private static string $subject = 'cap-book-ref';

	/**
	 * Time until token expires - Currently 2 days.
	 * @var float|int
	 */
	private static int|float $duration = ( 3600 * 24) * 2;

	/**
	 * Hashing algorithm used.
	 * @var string
	 */
	private static string $algorithm = 'HS256';

	/**
	 * Issues a new JWT and returns it.
	 *
	 * @param $user
	 * @return string
	 */
	public static function issue($user) {

		$expires = time() + self::$duration;

		$payload = array(
			"sub" => self::$subject,
			"name" => $user,
			"iat" => time(),
			"exp" => $expires,
		);
		$token = JWT::encode($payload, self::$secret, self::$algorithm);

		// Store token after creation
		self::store($user, $token, $expires);

		return $token;
	}

	/**
	 * Stores a JWT in the database.
	 *
	 * @param $user
	 * @param $token
	 * @param $expiry
	 * @return void
	 */
	public static function store($user, $token, $expiry) {
		$expiry = Carbon::parse($expiry)->toDateTimeString();
		$model = \Ceremonies\Models\Token::where('booking_id', $user)->first();
		if ($model) {
			$model->fill(['token' => $token, 'expiry' => $expiry]);
			$model->save();
		} else {
			(\Ceremonies\Models\Token::create([
				'booking_id' => $user,
				'token' => $token,
				'expiry' => $expiry
			]))->save();
		}
	}

	/**
	 * Checks if a JWT is valid.
	 *
	 * @param $token
	 * @return false|\stdClass
	 */
	public static function validate($token) {
		try {
			return JWT::decode($token, new Key(self::$secret, self::$algorithm));
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Invalidates an existing token.
	 *
	 * @return void
	 */
	public static function invalidate() {
		// TODO: Update invalidate state on token in DB. Should this use the user or the actual token?
	}

	/**
	 * Gets the data from a token.
	 *
	 * @return \stdClass
	 */
	public static function getData($token) {
		$tokenData = JWT::decode($token, new Key(self::$secret, self::$algorithm));
		return $tokenData;
	}

	/**
	 * Gets the token from the current request.
	 *
	 * @return string
	 */
	public static function get() {
		$header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
		$token = str_replace('Bearer ', '', $header);
		return trim($token);
	}

	public static function getTokenName() {
		$data = Token::getData(Token::get());
		return $data->name;
	}

    public static function isExpiring()
    {
        $token = self::getData(self::get());
        // Check if token is going to expire in the next 12 hours
        $expiry = Carbon::parse($token->exp);
        $now = Carbon::now();
        $diff = $expiry->diffInHours($now);
        return $diff < 12;
    }

}