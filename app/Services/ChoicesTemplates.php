<?php

namespace Ceremonies\Services;

use Ceremonies\Services\FormTemplates\BabyNaming;
use Ceremonies\Services\FormTemplates\CommitmentCeremony;
use Ceremonies\Services\FormTemplates\CpEnhancedCeremony;
use Ceremonies\Services\FormTemplates\CpTraditionalCeremony;
use Ceremonies\Services\FormTemplates\EnhancedCeremony;
use Ceremonies\Services\FormTemplates\StatutoryCeremony;
use Ceremonies\Services\FormTemplates\TraditionalCeremony;
use Ceremonies\Services\FormTemplates\VowRenewal;

/**
 * Form field generation has been split out into separate classes for
 * each ceremony. Both for readability and also IDE performance. I was
 * experiencing major lag when trying to work on this file previously.
 */
class ChoicesTemplates {

	// Complete - Needs double-checking
	public static function enhancedCeremony() {
		return EnhancedCeremony::generate();
	}

	// Complete - Needs double-checking
	public static function traditionalCeremony() {
		return TraditionalCeremony::generate();
	}

	// 90% complete - Fields missing
	public static function commitmentCeremony() {
		return CommitmentCeremony::generate();
	}

	// 90% complete - Fields missing
	public static function cpTraditionalCeremony() {
		return CpTraditionalCeremony::generate();
	}

	// Complete - Needs double-checking
	public static function cpEnhancedCeremony() {
		return CpEnhancedCeremony::generate();
	}

	// Complete - Needs double-checking
	public static function statutoryCeremony() {
		return StatutoryCeremony::generate();
	}

	// Complete - Needs double-checking
	public static function vowRenewalCeremony() {
		return VowRenewal::generate();
	}

	public static function babyNaming() {
		return BabyNaming::generate();
	}

}
