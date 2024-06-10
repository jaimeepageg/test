<?php

namespace Ceremonies\Services\FormTemplates;

use Ceremonies\Services\FormBuilder;

class CpTraditionalCeremony implements FormTemplate {

	/**
	 * @inheritDoc
	 */
	public static function generate() {
		$form = new FormBuilder();
		$form->addFields([
			$form->addSection( 'Step One', 'Contact and Basic Ceremony Details', [
				$form->addTitle( 'Contact Details' ),
				$form->addContent( 'Provide details of the primary contact in relation to this booking.' ),
				$form->addText( 'Contact Name', 'contact_name', '', false ),
				$form->addText( 'Home Telephone', 'home_telephone' , '', false ),
				$form->addText( 'Mobile Telephone', 'mobile_telephone', '', false ),
				$form->addText( 'Contact Email Address', 'contact_email_address', '', false ),
			] ),
			$form->addSection( 'Step Two', 'Initial Ceremony Details', [
				$form->addTitle( 'Initial Ceremony Details' ),
				$form->addSubtitle( 'Meeting the Registrar' ),
				$form->addSelect( 'On the day of your civil partnership, before your ceremony begins you will be seen by the registrar. Would you like to be seen by the registrar together or separately?', 'meeting_the_registrar', [
					'Together',
					'Separately'
				]),
				$form->addSubtitle( 'Couples Details' ),
				$form->addContent( 'Please enter your full names' ),
				$form->addText( 'Partner One Full Name', 'partner_one_name' ),
				$form->addText( 'Partner Two Full Name', 'partner_two_name' ),
 			]),
			$form->addSection( 'Step Three', 'Entrance', [
				$form->addSubtitle( 'Entrance' ),
                $form->addSelectConditional('Would you like to enter the Ceremony Room together or separately?', 'entering_marriage_room', [
                    $form->addSelectConditionalOption('Together', 'together', []),
                    $form->addSelectConditionalOption('Separately', 'separately', [
                        $form->addRadioConditional('If entering separately, do either of you wish to be accompanied?', 'will_be_accompanied', [
                            $form->addRadioOption('Neither wish to be accompanied', 'Neither'),
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
                    ]),
                ]),
			]),
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
			$form->addSection( 'Step Five', 'Promises', [
				$form->addContent( 'When making a declaration to each other, you may if you wish add an additional, more personal promise to each other.' ),
                $form->addCeremonyBookReference(url: FormBuilder::$TRADITIONAL_PROMISES_BOOK_URL),
				$form->addRadioConditional( 'These promises are optional. Please choose from the list provided.', 'promise_option', [
					$form->addRadioOption( 'We do not wish to quote a promise', 'none' ),
					$form->addRadioConditionalOption( 'We have chosen promise number', 'promise_number', [
						$form->addSelect( 'Select a promise number', 'promise_number', $form->getPromisesList())
					] ),
				] )
			] ),
            $form->addSection( 'Step Six', 'Exchange of Rings', [
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
                        $form->addText( 'What is their relationship to you?', 'ring_bearer_relationship', 'Ie. Family member, friend etc.' ),
                    ] ),
                ] ),
            ] ),

			$form->addSection( 'Step Seven', 'Readings', [
				$form->addContent( 'You may wish to include readings during your ceremony.  This is optional from the list provided' ),
                $form->addCeremonyBookReference(url: FormBuilder::$TRADITIONAL_READINGS_BOOK_URL),

                $form->addRadioConditional( 'Please indicate which reading option you would like', 'first_reading', [
					$form->addRadioOption( 'We do not wish to have a reading', 'none' ),
					$form->addRadioConditionalOption( 'A reading from those provided', 'provided_reading', [
						$form->addSelect( 'Please select a reading', 'provided_reading_one', $form->getReadingsList()),
                        $form->addText( 'Who will be reading your first reading?', 'reading_one_reader', 'The Celebrant would be happy to perform this duty for you if required' ),

                    ] ),
				] ),
			] ),
			$form->addSection( 'Step Eight', 'Music', [
                $form->addTitle( 'Music' ),
                $form->addRadioConditional('Please indicate which music option you would like', 'ro_music_option', [
                    $form->addRadioConditionalOption('Your own music (specify below)', 'own_music', [
                        $form->addContent('If you wish to choose your own music, please specify the title, artist, and if appropriate, the version of the tracks of your choice.'),
                        $form->addContent('We will set up your music choices on our digital music system so they are ready on the day of your ceremony.  If we have any problems with the tracks you have chosen we will contact you directly.'),
                        $form->addText('Whilst guests are assembling', 'ro_music_while_assembling'),
                        $form->addText('On entrance', 'ro_music_on_entrance'),
                        $form->addText('Whilst signing the schedule', 'ro_music_signing'),
                        $form->addText('Whilst departing', 'ro_music_on_departure'),
                    ]),
                    $form->addRadioOption('A classical selection provided by the registrar', 'registrar_classic'),
                    $form->addRadioOption('A modern selection provided by the registrar', 'registrar_modern'),
                ]),
			] ),
			$form->addSection( 'Step Nine', 'Guests and Witnesses', [
                $form->addTitle('Guests and Witnesses'),
				$form->addContent( 'Please note that this is the final step. Clicking the Submit Form button on this page will forward your ceremony choices onto us and you will no longer be able to edit it from this application.' ),
				$form->addText( 'Number of Guests', 'number_of_guests' ),
				$form->addSubtitle('Witnesses'),
				$form->addText( 'Name of witness 1', 'witness_1' ),
				$form->addText( 'Name of witness 2', 'witness_2' ),
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
