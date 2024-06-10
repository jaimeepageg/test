<?php

namespace Ceremonies\Services\FormTemplates;

use Ceremonies\Services\FormBuilder;

class BabyNaming implements FormTemplate {

	public static function generate() {
		$form = new FormBuilder();
		$form->addFields( [
			$form->addSection( 'Step One', 'Contact and Basic Ceremony Details', [
				$form->addTitle( 'Contact Details' ),
				$form->addContent( 'Provide details of the primary contact in relation to this booking.' ),
				$form->addText( 'Contact Name', 'contact_name', '', false ),
				$form->addText( 'Home Telephone', 'home_telephone' , '', false ),
				$form->addText( 'Mobile Telephone', 'mobile_telephone', '', false ),
				$form->addText( 'Contact Email Address', 'contact_email_address', '', false ),
				$form->addSubtitle( 'Venue Type' ),
				$form->addSelect( 'Where will you be holding the ceremony?', 'venue_type', $form->getVenueList())
			] ),
			$form->addSection( 'Step Two', 'Venue and Entrance', [
				$form->addTitle( 'Venue and Entrance' ),
				$form->addSubtitle( 'Date and Time of Ceremony' ),
				$form->addDate( 'Date of Ceremony', 'date_of_ceremony' ),
				$form->addTime( 'Time of Ceremony', 'time_of_ceremony' ),
				$form->addSubtitle( 'Parents Details' ),
				$form->addContent('Please enter the names fully, as you would like them read during the service.'),
				$form->addText('Partner 1 Full Name', 'partner_1_name'),
				$form->addText('Partner 2 Full Name', 'partner_2_name'),
				$form->addSubtitle('Child(s) Details'),
				$form->addContent('Please enter the names fully, as you would like them read during the service.'),
				$form->addText('Child 1 Full Name', 'child_1_name'),
				$form->addText('Child 2 Full Name', 'child_2_name'),
				$form->addText('Siblings names (if any)', 'sibling_names', '', false),
			] ),
			$form->addSection( 'Step Three', 'Parents Promises', [
				$form->addContent( 'An important part of the ceremony is the parents promises to the child, and to each other.'),
				$form->addTitle('Parents Promises to Child'),
				$form->addContent('Please choose one from our selection of promises on the attached PDF, and then select the choice in the box below.'),
				$form->addRadioConditional( 'These promises are optional. Please choose from the list provided.', 'promise_to_child_option', [
					$form->addRadioOption( 'We do not wish to quote a promise', 'none' ),
					$form->addRadioConditionalOption( 'We have chosen promise number', 'promise_to_child_number', [
						$form->addSelect( 'Select a promise number', 'promise_to_child_number', $form->getPromisesList())
					] ),
					$form->addRadioConditionalOption( 'We will provide our own promises', 'promise_to_child_uploaded', [
						$form->addFile( 'Please upload your promises', 'promise_to_child_uploaded' ),
					] ),
				] ),
				$form->addTitle('Parents Promises to each other'),
				$form->addContent('Please choose one from our selection of promises on the attached PDF, and then select the choice in the box below.'),
				$form->addRadioConditional( 'These promises are optional. Please choose from the list provided.', 'parents_promise_option', [
					$form->addRadioOption( 'We do not wish to quote a promise', 'none' ),
					$form->addRadioConditionalOption( 'We have chosen promise number', 'parents_promise_number', [
						$form->addSelect( 'Select a promise number', 'parents_promise_number', $form->getPromisesList())
					] ),
					$form->addRadioConditionalOption( 'We will provide our own promises', 'parents_promise_uploaded', [
						$form->addFile( 'Please upload your promises', 'parents_promise_uploaded' ),
					] ),
				] ),
			] ),
			$form->addSection( 'Step Four', 'Chosen Friends Promises', [
				$form->addContent( 'You may also wish to include friends and family to be a part of the ceremony.'),
				$form->addTitle('Chosen Friends Promises'),
				$form->addContent('Please choose one from our selection of promises on the attached PDF, and then select the choice in the box below.'),
				$form->addRadioConditional( 'These promises are optional. Please choose from the list provided.', 'friends_promise_option', [
					$form->addRadioOption( 'We do not wish to quote a promise', 'none' ),
					$form->addRadioConditionalOption( 'We have chosen promise number', 'friends_promise_number', [
						$form->addSelect( 'Select a promise number', 'friends_promise_number', $form->getPromisesList())
					] ),
					$form->addRadioConditionalOption( 'We will provide our own promises', 'friends_promise_uploaded', [
						$form->addFile( 'Please upload your promises', 'friends_promise_uploaded' ),
					] ),
				] ),
				$form->addText('Please state the name(s) of your chosen friends', 'chosen_friends_names', '', false),
			] ),
			$form->addSection( 'Step Five', 'Grandparents Promises', [
				$form->addContent( 'You may also wish to include friends and family to be a part of the ceremony.'),
				$form->addTitle('Grandparents Promises'),
				$form->addContent('Please choose one from our selection of promises on the attached PDF, and then select the choice in the box below.'),
				$form->addRadioConditional( 'These promises are optional. Please choose from the list provided.', 'grandparent_promises_option', [
					$form->addRadioOption( 'We do not wish to quote a promise', 'none' ),
					$form->addRadioConditionalOption( 'We have chosen promise number', 'grandparent_promises_number', [
						$form->addSelect( 'Select a promise number', 'grandparent_promises_number', $form->getPromisesList())
					] ),
					$form->addRadioConditionalOption( 'We will provide our own promises', 'grandparent_promises_uploaded', [
						$form->addFile( 'Please upload your promises', 'grandparent_promises_uploaded' ),
					] ),
				] ),
				$form->addText('Please state the name(s) of any grandparents attending the ceremony', 'grandparent_names', '', false),
			] ),
			$form->addSection( 'Step Six', 'Readings', [
				$form->addContent( 'You may wish to include one or two readings during your ceremony.  These are optional from the list provided' ),
				$form->addRadioConditional( 'Please indicate which first reading option you would like', 'first_reading', [
					$form->addRadioOption( 'We do not wish to have a reading', 'none' ),
					$form->addRadioConditionalOption( 'A reading from those provided', 'provided_reading', [
						$form->addSelect( 'Please select a reading', 'provided_reading_one', $form->getReadingsList())
					] ),
					$form->addRadioOption( 'We have attached a file containing our own reading', 'reading_one_uploaded' )
				] ),
				$form->addText( 'Who will be reading your first reading?', 'reading_one_reader', 'The Celebrant would be happy to perform this duty for you if required' ),
				$form->addRadioConditional( 'Please indicate which second reading option you would like', 'second_reading', [
					$form->addRadioOption( 'We do not wish to have a reading', 'none' ),
					$form->addRadioConditionalOption( 'A reading from those provided', 'provided_reading', [
						$form->addSelect( 'Please select a reading', 'provided_reading_two', $form->getReadingsList())
					] ),
					$form->addRadioOption( 'We have attached a file containing our own reading', 'reading_two_uploaded' )
				] ),
				$form->addFile( 'Please upload any required readings for your ceremony', 'ceremony_readings' ),
			] ),
			$form->addConditionalSections( 'Step Seven', 'Music', 'venue_type', [
				$form->addConditionalSection( 'registration_office', [
					$form->addTitle( 'Music' ),
					$form->addRadioConditional( 'Please indicate which music option you would like', 'music_option', [
						$form->addRadioConditionalOption( 'Your own music (specify below)', 'own_music', [
							$form->addContent( 'If you wish to choose your own music, please specify the title, artist, and if appropriate, the version of the tracks of your choice.'),
							$form->addContent('We will set up your music choices on our digital music system so they are ready on the day of your ceremony.  If we have any problems with the tracks you have chosen we will contact you directly.'),
							$form->addText( 'Whilst guests are assembling', 'music_while_assembling' ),
							$form->addText( 'On entrance of bride/groom', 'music_on_entrance' ),
							$form->addText( 'Whilst signing the schedule', 'music_signing' ),
							$form->addText( 'Whilst departing', 'music_on_departure' ),
						] ),
						$form->addRadioOption( 'A classical selection provided by the registrar', 'registrar_classic' ),
						$form->addRadioOption( 'A modern selection provided by the registrar', 'registrar_modern' ),
						$form->addRadioConditionalOption( 'Other music choice (For example, artist or band)', 'music_other_choice', [
							$form->addText( 'Details about other music choice', 'music_other_choice', 'This will need to be arranged with the registration office. The registrars may need to contact you about the details.' ),
						] )
					] )
				] ),
				$form->addConditionalSection( 'approved_venue', [
					$form->addTitle( 'Music' ),
					$form->addRadioConditional( 'Please indicate which music option you would like', 'music_option', [
						$form->addRadioConditionalOption( 'Your own music (specify below)', 'own_music', [
							$form->addContent( 'If you wish to choose your own music, please specify the title and artist.
Please Note: Music should be arranged and organised directly with the venue. Please make sure you discuss this with the venue and/or ceremony planner.' ),
							$form->addText( 'Whilst guests are assembling', 'music_while_assembling' ),
							$form->addText( 'On entrance of bride/groom', 'music_on_entrance' ),
							$form->addText( 'Whilst signing the schedule', 'music_signing' ),
							$form->addText( 'Whilst departing', 'music_on_departure' ),
						] ),
						$form->addRadioConditionalOption( 'Other music choice (For example, artist or band)', 'music_other_choice', [
							$form->addText( 'Details about other music choice', 'music_other_choice', 'This will need to be arranged with the registration office. The registrars may need to contact you about the details.' ),
						] )
					] )
				] ),
			] ),
			$form->addSection( 'Step 10', 'Guests and Witnesses', [
                $form->addTitle('Guests and Witnesses'),
				$form->addContent( 'Please note that this is the final step. Clicking the Submit Form button on this page will forward your ceremony choices onto us and you will no longer be able to edit it from this application.' ),
				$form->addText( 'Number of Guests', 'number_of_guests' ),
				$form->addSubtitle( 'Special Mention' ),
				$form->addTextarea( 'Is there anything you would like to mention to enable your day to run smoothly?', 'special_requests', '', false ),
				$form->addSpecialCheck( 'I have completed my form and wish to submit it.', 'form_confirmation' ),
			] )
		]);

        return [
            'fields' => $form->getFieldsFlat(),
            'form'   => $form->form,
        ];
	}

}
