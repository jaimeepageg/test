<?php

namespace Ceremonies\Controllers;

use Carbon\Carbon;
use Ceremonies\Core\Bootstrap;
use Ceremonies\Models\Booking;
use Ceremonies\Models\Bookings\Choices;
use Ceremonies\Models\Bookings\ChoicesQuestion;
use Ceremonies\Models\Client;
use Ceremonies\Repositories\BookingRepository;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/**
 * This entire class is used for data migration and has
 * one time use. Best practices may go out the window.
 */
class MigrationController {

	private $bookingRepo;
	private $map;

	public function __construct() {
		$this->bookingRepo = Bootstrap::container()->get(BookingRepository::class);
	}

	/**
	 * Takes a XSLX file and imports the data
	 * into the database.
	 *
	 * @return void
	 */
	public function import() {

		// Load data from file
		$data = $this->loadDataFromFile();
		$this->loadMap();

		foreach ($data as $row) {
			// Generate booking and clients
			$booking = $this->generateBooking($row);
			$this->bookingRepo->initialBookingSetup($booking);
			$booking->refresh();
			$this->importChoicesToBooking($booking, $data);
		}

	}

	/**
	 * Loads the data from the spreadsheet and returns it as
	 * an array.
	 *
	 * @return array
	 */
	private function loadDataFromFile() : array {

		// Pull data from spreadsheet
		$reader = new Xlsx;
		$spreadsheet = $reader->load(CER_STORAGE_ROOT . 'data-import.xlsx');
		$worksheet = $spreadsheet->getActiveSheet();
		$rows = $worksheet->toArray();

		// Get first row as keys
		$keys = array_shift($rows);

		$data = [];

		// Attach keys to each row - array_fill_keys caused strange issues
		foreach($rows as $row) {
			$rowWithKeys = [];
			foreach ($row as $itemKey => $item) {
				$rowWithKeys[$keys[$itemKey]] = $item;
			}
			$data[] = $rowWithKeys;
		}

		return $data;

	}

	/**
	 * Finds the answer for a form field based on the
	 * field name and the number given in the import.
	 */
	private function findAnswer($key, $value): string {
		// TODO: Implement
		return '';
	}

	/**
	 * Generate an initial booking within the system for
	 * a row in the sheet.
	 *
	 * @return void
	 */
	private function generateBooking(array $data): Booking {

		$booking = new Booking();
		$booking->zip_reference = $data['ApplicationID'];
		$booking->email_address = $data['EmailAddress'];

//		$booking->office = $this->zipporah->removeVenuePrefix($zipBooking->ResourceCategoryName);
//		$booking->type = $this->zipporah->getBookingTypeName($zipBooking->BookingTypeId);

		// Always blank/null
		$booking->zip_notes = '';
		$booking->zip_related_bookings = null;
		$booking->booking_cost = null;
		$booking->raw_data = json_encode($data);

		// Conditional data
		/* if ($data['CeremonyDay']) {
			$date = Carbon::parse($data['CeremonyDay'] . '-' . $data['CeremonyMonth'] . '-' . $data['CeremonyYear'] . ' ' . $data['CeremonyTime']);
			$booking->booking_date = $date->format('Y-m-d H:i:s');
			if ($date->isPast()) {
				$booking->status = 'Complete';
			}
		} */

		$booking->save();

		// Setup relational data
//		$this->setupClients($booking, $data);
//		$this->setupTasks($booking, $data);

		// Add note against booking to say it's been imported
		$booking->addNote('Booking has been imported from previous system. Data may be incomplete.');

		return $booking;
	}

	private function setupClients(Booking $booking, array $data) : void {

		// Convert numbers to a combined string
		$phoneString =  rtrim(implode(' | ', [$data['HomePhone'], $data['MobilePhone']]), '|');

		$booking->clients()->createMany([
			[
				'first_name' => $data['PartnerAName'],
				'last_name' => '',
				'email' => $data['EmailAddress'],
				'phone' => $phoneString,
				'is_primary' => $data['ContactName'] === $data['PartnerAName'],
				'zip_id' => null,
			],
			[
				'first_name' => $data['PartnerBName'],
				'last_name' => '',
				'email' => $data['EmailAddress'],
				'phone' => $phoneString,
				'is_primary' => $data['ContactName'] === $data['PartnerBName'],
				'zip_id' => null,
			]
		]);

	}

	private function setupTasks(Booking $booking, array $data) : void {

		// Setup

	}

	private function importChoicesToBooking(Booking $booking, array $data) {

		// Loop through all items that are choices related
		// If the value is not a number, store the value straightaway
		// If it is a number, we need to figure out what the associated value is

		$blacklist = $this->getChoicesBlacklist();

		// TODO: Create choices form, attach to booking
		$form = new Choices();
		$form->save();

		foreach ($data as $key => $value) {

			if (in_array($key, $blacklist)) {
				continue;
			}

			// Get new name/question for old key
			$questionLabels = $this->getQuestionInfo($key, $value, $data);

			// Create question and populate
			$question = new ChoicesQuestion();
			$question->form_id = $form->id;
			$question->name = $questionLabels['name'];
			$question->question = $questionLabels['question'];
			$question->answer = is_numeric($value) ? $questionLabels['answers'][$value] : $value;
			$question->save();

		}

	}

	/**
	 * Loads the JSON file with the old to new
	 * question format map.
	 *
	 * @return void
	 */
	private function loadMap() {
		if ($this->map) {
			return;
		}

		$file = file_get_contents(CER_STORAGE_ROOT . 'data-import-map.json');
		$this->map = json_decode($file, true);

	}

	private function getQuestionInfo(string $key, string $value, array $rowData) : array {

		// Need a big map of old questions to new questions
		// Map needs to include each potential answer
		$mapData = $this->map[$key];

		// If the data in the map is a string, copy the string
		if(is_string($mapData)) {
			return [
				'name' => $mapData,
				'question' => $mapData,
				'answers' => $value,
			];
		}

		if (isset($mapData->special)) {
			return [
				'name' => $mapData->key,
				'question' => $mapData->key,
				'answers' => $this->processSpecialField($key, $value, $rowData)
			];
		}

		return [
			'name' => $mapData->key,
			'question' => $mapData->key,
			'answers' => (array) $mapData->values
		];

	}

	private function getChoicesBlacklist() {
		return [
			'ApplicationID',
			'UserSession',
			'UserCode',
			'CreationCode',
			'ContactName',
			'HomePhone',
			'MobilePhone',
			'EmailAddress',
			'CoupleType',
			'AdminEmail',
			'CopiedOrder',
			'ReadingName',
			'RefEmailSent',
		];
	}

	/**
	 * Processes any fields marked as special in the map.
	 * Fields that may manually need data conversion etc.
	 *
	 * @param string $key
	 * @param string $value
	 * @return string
	 */
	private function processSpecialField(string $key, string $value, string $rowData) {
		switch ($key) {
			case "CeremonyDate":
				$date = Carbon::parse($rowData['CeremonyDay'] . '-' . $rowData['CeremonyMonth'] . '-' . $rowData['CeremonyYear'] . ' ' . $rowData['CeremonyTime']);
				return $date->format('Y-m-d H:i:s');
			case "CeremonyTime":
				return "";
			case "DeclarationType":
				return "";
			case "MusicAssembleName":
				return "";
			case "MusicEntranceName":
				return "";
			case "MusicSigningName":
				return "";
			case "MusicDepartingName":
				return "";
			case "SubmittedDate":
				return "";
		}
	}

	public function columns() {
		//		ApplicationID,
		//		UserSession,
		//		UserCode,
		//		CreationCode,

		//		ContactName -> contact_name,
		//		HomePhone -> home_telephone,
		//		MobilePhone -> mobile_telephone,
		//		EmailAddress -> contact_email_address,
		//		CoupleType -> who_is_getting_married,
		//		RegistrarMeeting -> meeting_the_registrar,
		//		CeremonyDay -> date_of_ceremony,
		//		CeremonyMonth -> date_of_ceremony,
		//		CeremonyYear -> date_of_ceremony,
		//		CeremonyDate -> date_of_ceremony,
		//		CeremonyTime -> time_of_ceremony,
		//		VenueType -> venue_type,
		//		PartnerAName -> partner_one_name,
		//		PartnerBName -> partner_two_name,
		//		VenueID -> venue_details,
		//		VenueName -> venue_details,
		//		EntranceType -> entering_marriage_room,
		//		EntranceAccompanied -> entering_accompanying,
		//		EntranceHandStatement -> gives_name_in_hand_name,
		//		EntranceHandName -> who_gives_name_in_hand,
		//		DeclarationType -> declaration_option,
		//		RingsWho -> who_will_give_rings,
		//		RingsPresent -> ring_bearers_name,
		//		RingsPresentName -> who_presents_rings,
		//		PromiseType -> promise_option,
		//		PromiseNumber -> promise_number,
		//		ReadingType -> first_reading,
		//		ReadingNumber -> provided_reading_one,
		//		MusicType -> music_option,
		//		MusicAssembleName -> music_while_assembling,
		//		MusicAssembleTrack -> music_while_assembling,
		//		MusicEntranceName -> music_on_entrance,
		//		MusicEntranceTrack -> music_on_entrance,
		//		MusicSigningName -> music_signing,
		//		MusicSigningTrack -> music_signing,
		//		MusicDepartingName -> music_on_departure,
		//		MusicDepartingTrack -> music_on_departure,
		//		GuestsNumber -> number_of_guests,
		//		WitnessA -> witness_1,
		//		WitnessB -> witness_2
		//		AdditionalInfo -> special_requests,
		//		SubmittedDate -> UPDATE TASK: form has been submitted,
		//		StatusID,
		//		AdminEmail,
		//		CopiedOrder,
		//		ReadingName -> reading_one_reader,
		//		RefEmailSent -> ADD NOTE: Ref email sent,
		//		WeddingReference,
		//		Reading2Type -> second_reading,
		//		Reading2Number -> provided_reading_two,
		//		Reading2Name -> reading_two_reader,
		//		MusicOtherText -> music_other_choice,
		//		WitnessC -> witness_3,
		//		WitnessD -> witness_4
	}

}
