<?php

namespace Ceremonies\Services\FormTemplates;

interface FormTemplate {

	/**
	 * Generates all the fields for a
	 * given form.
	 *
	 * @return array
	 */
	public static function generate();

}
