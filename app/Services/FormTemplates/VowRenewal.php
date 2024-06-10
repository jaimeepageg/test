<?php

namespace Ceremonies\Services\FormTemplates;

use Ceremonies\Services\FormBuilder;

class VowRenewal implements FormTemplate {

	/**
	 * @inheritDoc
	 */
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
				$form->addSelect( 'Where will you be getting married', 'venue_type', [
					[ 'label' => 'Registration Office', 'value' => 'registration_office' ],
					[ 'label' => 'An Approved Venue', 'value' => 'approved_venue' ],
				] )
			] ),
			$form->addSection( 'Step Two', 'Initial Ceremony Details', [
				$form->addTitle( 'Initial Ceremony Details' ),
				$form->addSubtitle( 'Venue Details' ),
				$form->addSelect( 'Please indicate which venue your ceremony will take place', 'venue_details', $form->getVenueList()),
				$form->addSelect( 'On the day of your marriage, before your ceremony begins you will be seen by the registrar. Would you like to be seen by the registrar together or separately?', 'meeting_the_registrar', [
					'Together',
					'Separately'
				] ),
				$form->addSubtitle( 'Meeting the Registrar' ),
				$form->addSubtitle( 'Couples Details' ),
				$form->addContent( 'Please enter the names fully, as you would like them read during the service.' ),
				$form->addText( 'Partner One Full Name', 'partner_one_name' ),
				$form->addText( 'Partner Two Full Name', 'partner_two_name' )
			] ),
			$form->addSection( 'Step Three', 'Venue and Entrance', [
				$form->addTitle( 'Venue and Entrance' ),
				$form->addSubtitle( 'Date and Time of Ceremony' ),
				$form->addDate( 'Date of Ceremony', 'date_of_ceremony' ),
				$form->addTime( 'Time of Ceremony', 'time_of_ceremony' ),
				$form->addSubtitle( 'Entrance' ),
				$form->addContent( 'If entering separately the groom will need to arrive 15 minutes before the ceremony and the bride 10 minutes before the ceremony.' ),
				$form->addSelect( 'Would you like to enter the Ceremony Room together or separately?', 'entering_marriage_room', [
					'Together',
					'Separately'
				] ),
                $form->addRadioConditional('If entering separately, do either of you wish to be accompanied?', 'will_be_accompanied', [
                    $form->addRadioConditionalOption('{{partner_one_name}} wishes to be accompanied', '{{partner_one_name}} to be accompanied', [
                        $form->addText('Who will perform this duty?', 'person_accompanying'),
                    ]),
                    $form->addRadioConditionalOption('{{partner_two_name}} wishes to be accompanied', '{{partner_two_name}} to be accompanied', [
                        $form->addText('Who will perform this duty?', 'person_accompanying'),
                    ]),
                    $form->addRadioConditionalOption('Both wish to be accompanied', 'Both to be accompanied', [
                        $form->addText('Who will accompany {{partner_one_name}}?', 'person_accompanying_partner_1'),
                        $form->addText('Who will accompany {{partner_two_name}}?', 'person_accompanying_partner_2'),

                    ]),

                ]),
			] ),
			$form->addSection( 'Step Four', 'Promises', [
				$form->addContent( 'When speaking the contracting words to each other, you may if you wish add an additional, more personal promise to each other.' ),
				$form->addRadioConditional( 'These promises are optional. Please choose from the list provided.', 'promise_option', [
					$form->addRadioOption( 'We do not wish to quote a promise', 'none' ),
					$form->addRadioConditionalOption( 'We have chosen promise number', 'promise_number', [
						$form->addSelect( 'Select a promise number', 'promise_number', $form->getPromisesList())
					] ),
					$form->addRadioConditionalOption( 'We will provide our own promises', 'promise_uploaded', [
						$form->addFile( 'Please upload your promises', 'promise_uploaded' ),
					] ),
				] )
			] ),
			$form->addSection( 'Step Five', 'Exchange of Rings', [
				$form->addTitle( 'Exchange of Rings' ),
				$form->addRadio( 'Who will be giving rings?', 'who_will_give_rings', [
					$form->addRadioOption( 'We will both be giving rings', 'both' ),
					$form->addRadioOption( '{{partner_one_name}} will be giving a ring', 'partner_one' ),
					$form->addRadioOption( '{{partner_two_name}} will be giving a ring', 'partner_two' ),
					$form->addRadioOption( 'Neither of us will be giving rings', 'none' ),
				] ),
				$form->addRadioConditional( 'If rings are being exchanged, would you like someone to present the ring(s)?', 'who_presents_rings', [
					$form->addRadioOption( 'No', 'no' ),
					$form->addRadioConditionalOption( 'Yes', 'yes', [
						$form->addText( 'What is the ring bearers name?', 'ring_bearers_name' ),
						$form->addText( 'What is their relationship to you?', 'ring_bearer_relationship', 'Ie. Best man, son/daughter, friend etc.' ),
					] ),
				] ),
				$form->addContent( 'When exchanging rings, you may choose the accompanying words. You can choose from our list or provide your own.' ),
				$form->addCeremonyBookReference(),
				$form->addContent('Which ring promise would you like?'),
				$form->addSelect( 'Reading Number', 'reading_number', $form->getReadingsList()),
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
				$form->addText( 'Who will be reading your second reading?', 'reading_two_reader', 'The Celebrant would be happy to perform this duty for you if required' ),
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
			$form->addSection( 'Step Eight', 'Guests and Witnesses', [
                $form->addTitle('Guests and Witnesses'),
				$form->addContent( 'Please note that this is the final step. Clicking the Submit Form button on this page will forward your ceremony choices onto us and you will no longer be able to edit it from this application.' ),
				$form->addText( 'Number of Guests', 'number_of_guests' ),
				$form->addSubtitle( 'Inclusion of Children'),
				$form->addContent('If you want us to mention your children in the ceremony we can do this.'),
				$form->addTextarea('If you wish to include children, please state their names', 'included_children'),
				$form->addSubtitle( 'Special Mention' ),
				$form->addTextarea( 'Is there anything you would like to mention to enable your day to run smoothly?', 'special_requests', '', false ),
				$form->addSpecialCheck( 'I have completed my form and wish to submit it.', 'form_confirmation' ),
			] )
		] );

        return [
            'fields' => $form->getFieldsFlat(),
            'form'   => $form->form,
        ];
	}
}
