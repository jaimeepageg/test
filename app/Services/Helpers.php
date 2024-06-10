<?php

namespace Ceremonies\Services;

class Helpers
{

    /**
     * Checks if the current page is the SPA entry point.
     *
     * @return bool
     */
    public static function isPluginPage()
    {
        return is_admin() && isset($_GET['page']) && $_GET['page'] === 'ceremonies';
    }

    /**
     * Checks if the SPA is in dev mode.
     *
     * @return mixed
     */
    public static function isInDevMode()
    {
        return CEREMONIES_DEV_MODE;
    }


    /**
     * Converts an object to an assoc array,
     * borrowed from: https://stackoverflow.com/a/19495118
     *
     * @param $object
     * @return array
     */
    public static function objectToArray($object)
    {
        if (is_object($object)) {
            // Gets the properties of the given object
            // with get_object_vars function
            $object = get_object_vars($object);
        }

        if (is_array($object)) {
            /*
            * Return array converted to object
            * Using __FUNCTION__ (Magic constant)
            * for recursive call
            */
            return array_map([self::class, 'objectToArray'], $object);
        } else {
            // Return array
            return $object;
        }
    }


    /**
     * Pulls multiple $_FILES from a request and returns them in
     * a slightly more usable format.
     *
     * @return array
     */
    public static function getFiles(string $field)
    {
        $files = [];

        foreach($_FILES[$field]['name'] as $key => $value) {
            $files[] = [
                'name' => $_FILES[$field]['name'][$key],
                'type' => $_FILES[$field]['type'][$key],
                'tmp_name' => $_FILES[$field]['tmp_name'][$key],
                'error' => $_FILES[$field]['error'][$key],
                'size' => $_FILES[$field]['size'][$key]
            ];
        }

        return $files;

    }

	/**
	 * Obfuscates an email address to only partially
	 * reveal it.
	 *
	 * @param $email
	 * @return string
	 */
	public static function hideEmail($email) {
		$em   = explode("@",$email);
		$name = implode('@', array_slice($em, 0, count($em)-1));
		$len  = floor(strlen($name)/2);

		return substr($name,0, $len) . str_repeat('*', $len) . "@" . end($em);
	}

	/**
	 * Obfuscates a phone number to only partially
	 * reveal it.
	 *
	 * @param $phone
	 * @return mixed
	 */
	public static function hidePhone($phone) {

		if ($phone !== '') {
			$length = strlen($phone) ;
			for($i = $length - 4; $i < $length; $i++){
				$phone[$i] = "*";
			}
		}

		return $phone;
	}

}