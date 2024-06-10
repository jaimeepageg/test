<?php

namespace Ceremonies\Services\FormTemplates;

use Ceremonies\Services\FormBuilder;

class CpEnhancedCeremony implements FormTemplate {

	/**
	 * @inheritDoc
	 */
	public static function generate() {
		$form = new FormBuilder();
		$form->addFields( [
			$form->addSection( 'Step One', 'Contact Details', [
				$form->addTitle( 'Contact Details' ),
				$form->addContent( 'Provide details of the primary contact in relation to this booking.' ),
				$form->addText( 'Contact Name', 'contact_name', '', false ),
				$form->addText( 'Home Telephone', 'home_telephone' , '', false ),
				$form->addText( 'Mobile Telephone', 'mobile_telephone', '', false ),
				$form->addText( 'Contact Email Address', 'contact_email_address', '', false ),
			] ),
			$form->addSection( 'Step Two', 'Initial Ceremony Details', [
				$form->addTitle( 'Initial Ceremony Details' ),
				$form->addSelect( 'On the day of your civil partnership, before your ceremony begins you will be seen by the registrar. Would you like to be seen by the registrar together or separately?', 'meeting_the_registrar', [
					'Together',
					'Separately'
				] ),
				$form->addSubtitle( 'Meeting the Registrar' ),
				$form->addContent( 'Please enter your full names' ),
				$form->addText( 'Partner One Full Name', 'partner_one_name' ),
				$form->addText( 'Partner Two Full Name', 'partner_two_name' )
			] ),
			$form->addSection( 'Step Three', 'Entrance', [

				$form->addTitle( 'Entrance' ),
				$form->addContent( 'If entering separately one of you will need to arrive 15 minutes before the ceremony and the other 10 minutes before the ceremony.' ),
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
            $form->addSection('Step Four', 'Declaratory and Contracting Vows', [
                $form->addTitle('Declaratory and Contracting Vows'),
                $form->addContent('To form a legal partnership, both required to speak the declaratory and contracting words in one of three prescribed forms.'),
                $form->addDeclarations('Please choose your preferred option below', 'declaration_option', [
                    $form->addDeclarationOption(
                        'option_1',
                        'I do solemnly declare that I know not of any lawful impediment why I, ........................ may not be joined in matrimony to .........................',
                        'I call upon these persons here present to witness that I, ........................ do take thee ........................ to be my lawful wedded {{partner_one_type}}/{{partner_two_type}}.'
                    ),
                    $form->addDeclarationOption(
                        'option_2',
                        'I declare that I know of no legal reason why I, ........................ may not be joined in marriage to .........................',
                        'I take thee ........................ to be my wedded {{partner_one_type}}/{{partner_two_type}}.'
                    ),
                    $form->addDeclarationOption(
                        'option_3',
                        'By replying \'I am\' to the question \'Are you ........................ free lawfully to marry ........................ ?\'.',
                        'I, ........................ take you ........................ to be my wedded {{partner_one_type}}/{{partner_two_type}}.'
                    ),
                ]),
                $form->addContent('Note both parties will say the same declaratory and contracting vows.'),
            ]),
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
				$form->addCeremonyBookReference(url: FormBuilder::$ENHANCED_BOOK_URL),
				$form->addRadioConditional( 'Which ring promise would you like?', 'ring_wording', [
					$form->addRadioConditionalOption( 'From the selection provided', 'book_reading', [
						$form->addSelect( 'Reading Number', 'reading_number', $form->getRingPromises())
					] ),
					$form->addRadioConditionalOption( 'Our own ring promise', 'uploaded_reading', [
						$form->addFile( 'Please attach your reading', 'reading_file' ),
					] ),
				] )
			] ),
			$form->addSection( 'Step Six', 'Sand Ceremony & Joining of Hands Ceremony', [
				$form->addTitle( 'Joining of Hands and Sand Ceremony' ),
				$form->addContent( 'With our enhanced ceremony package you can include either a joining of hands or a sand ceremony.' ),
                $form->addSubtitle( 'Joining of Hands Ceremony' ),
                $form->addContent( 'Joining of Hands Ceremonies can provide a modern quirky addition to the traditional ceremony and becoming increasingly popular. It shows the joining of two people by the holding of hands. How you hold your hands is your choice, there is no right or wrong way. You may wish for your hands to be ‘tied’ with ribbons or cords of your choice. Each of you have your chosen material and we use these to place them over your hands. We will then wrap them to create a loose knot, that the two of you will complete. The colours that you choose is completely up to you.' ),
				$form->addSubtitle( 'Sand Ceremony' ),
				$form->addContent( 'There will be three glass vessels in total. Two of them will be the same size, and each of them will be filled with sand, (a different colour in each one) to represent each of you. There will also be a larger glass bottle and each of you will take it in turns to pour your sand into it. This will form a beautiful design, created by the two of you. This is a representation as two become one.' ),
				$form->addRadio( 'Which option would you prefer for your ceremony?', 'sand_or_joining_hands', [
					[ 'label' => 'We do not wish to include this element in our ceremony', 'value' => 'none' ],
					[ 'label' => 'We have chosen a sand ceremony', 'value' => 'sand_ceremony' ],
					[ 'label' => 'We have chosen a joining of hands ceremony', 'value' => 'joining_hands' ],
				] ),
			] ),
			$form->addConditionalSections( 'Step Six - Part Two', 'Sand Ceremony & Joining of Hands Ceremony', 'sand_or_joining_hands', [
				$form->addConditionalSection( 'sand_ceremony', [
					$form->addContent( 'There will be three glass vessels in total. Two of them will be the same size, and each of them will be filled with sand, (a different colour in each one) to represent each of you. There will also be a larger glass bottle and each of you will take it in turns to pour your sand into it. This will form a beautiful design, created by the two of you. This is a representation as two become one.' ),
					$form->addSubtitle( 'Sand & Glassware' ),
					$form->addRadio( 'Please indicate from the following', 'sand_and_glassware', [
						$form->addRadioOption( 'We will provide our own sand & glassware', 'we_provide' ),
						$form->addRadioOption( 'Please provide sand & glassware for us', 'you_provide' ),
					] ),
					$form->addSubtitle( 'Accompanying Words' ),
					$form->addContent( 'You can choose the words that we read, whilst you are pouring your sand, from the choices provided, making it personal to you.' ),
					$form->addSelect( 'We have chosen reading number', 'reading_number', $form->getReadingsList())
				] ),
				$form->addConditionalSection( 'joining_hands', [
					$form->addContent( 'Joining of Hands Ceremonies can provide a modern quirky addition to the traditional ceremony and becoming increasingly popular. It shows the joining of two people by the holding of hands.
How you hold your hands is your choice, there is no right or wrong way.
You may wish for your hands to be ‘tied’ with ribbons or cords of your choice. Each of you have your chosen material and we use these to place them over your hands. We will then wrap them to create a loose knot, that the two of you will complete. The colours that you choose is completely up to you.' ),
					$form->addSubtitle( 'Ribbons and Cords' ),
					$form->addContent( '
	                    - You will need to provide the ribbon/cord<br/>
	                    - A minimum of 2 pieces are required<br/>
	                    - Please ensure your ribbon/cord is a minimum of 1 metre each<br/>
	                    - We will guide you through the process<br/>
	                ' ),
					$form->addSubtitle( 'Accompanying Words' ),
					$form->addContent( 'You can choose the words that we read whilst you join hands from the choices provided, making it personal to you.' ),
					$form->addSelect( 'We have chosen reading number', 'accompanying_words_reading_number', $form->getReadingsList())
				] )
			] ),
			$form->addSection( 'Step Seven', 'Readings', [
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
			$form->addConditionalSections( 'Step Eight', 'Music', 'venue_type', [
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
							$form->addText( 'On entrance', 'music_on_entrance' ),
							$form->addText( 'Whilst signing the schedule', 'music_signing' ),
							$form->addText( 'Whilst departing', 'music_on_departure' ),
						] ),
                        $form->addRadioOption('A classical selection provided by the registrar', 'registrar_classic'),
                        $form->addRadioOption('A modern selection provided by the registrar', 'registrar_modern'),
						$form->addRadioConditionalOption( 'Other music choice (For example, artist or band)', 'music_other_choice', [
							$form->addText( 'Details about other music choice', 'music_other_choice', 'This will need to be arranged with the registration office. The registrars may need to contact you about the details.' ),
						] )
					] )
				] ),
			] ),
			$form->addSection( 'Step Nine', 'Guests and Witnesses', [
                $form->addTitle('Guests and Witnesses'),
				$form->addContent( 'Please note that this is the final step. Clicking the Submit Form button on this page will forward your ceremony choices onto us and you will no longer be able to edit it from this application.' ),
				$form->addText( 'Number of Guests', 'number_of_guests' ),
				$form->addSubtitle( 'Witnesses' ),
				$form->addText( 'Name of witness 1', 'witness_1' ),
				$form->addText( 'Name of witness 2', 'witness_2' ),
				$form->addText( 'Name of witness 3', 'witness_3', '', false ),
				$form->addText( 'Name of witness 4', 'witness_4', '', false ),
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
