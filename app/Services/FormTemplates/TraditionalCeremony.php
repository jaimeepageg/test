<?php

namespace Ceremonies\Services\FormTemplates;

use Ceremonies\Services\FormBuilder;

class TraditionalCeremony implements FormTemplate
{

    /**
     * @inheritDoc
     */
    public static function generate()
    {
        $form = new FormBuilder();

        $form->addFields([
            $form->addSection('Step One', 'Contact and Basic Ceremony Details', [
                $form->addTitle('Contact Details'),
                $form->addContent('Provide details of the primary contact in relation to this booking.'),
                $form->addText('Contact Name', 'contact_name', '', false),
                $form->addText('Home Telephone', 'home_telephone', '', false),
                $form->addText('Mobile Telephone', 'mobile_telephone', '', false),
                $form->addText('Contact Email Address', 'contact_email_address', '', false),
                
                $form->addSubtitle('Initial Ceremony Details'),
                $form->addSelect('Please indicate the parties to be married', 'who_is_getting_married', [
                    'Bride and Groom',
                    'Bride and Bride',
                    'Groom and Groom'
                ]),
                $form->addContent('Details of the {{coupleType}}'),
                $form->addText('{{partnerOneType}}\'s Full Name', 'partner_one_name'),
                $form->addText('{{partnerTwoType}}\'s Full Name', 'partner_two_name'),
            ]),
            $form->addSection('Step Two', 'Registrar and Entrance', [
                $form->addTitle('Meeting the Registrar'),
                $form->addContent('On the day of your ceremony each of you will have a pre-marriage interview with the Registrar. Would you like to be seen together or separately? '),
                $form->addSelect('See the registrar together or separately?', 'meeting_the_registrar', [
                    'Together',
                    'Separately'
                ]),
                $form->addTitle('Entrance'),
                $form->addContent('If entering the ceremony separately the groom will need to arrive approximately 20 minutes before the ceremony and the bride 10 minutes before. If together you can arrive around 10 minutes prior to your ceremony. '),
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
                $form->addRadioConditional('Do you wish to include the question \'who gives <em>insert name</em>\' hand in marriage?', 'who_gives_name_in_hand', [
                    $form->addRadioConditionalOption('Yes', 'yes', [
                        $form->addText('Who will perform this duty?', 'who_performs_gives_name_in_hand_duty'),
                    ]),
                    $form->addRadioOption('No', 'no')
                ]),
            ]),
            $form->addSection('Step Three', 'Declaratory and Contracting Vows', [
                $form->addTitle('Declaratory and Contracting Vows'),
                $form->addContent('To form a legal marriage, both the {{who_is_getting_married}} are required to speak the declaratory and contracting words in one of three prescribed forms.'),
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
            $form->addSection('Step Four', 'Promises', [
                $form->addTitle('Promises'),
                $form->addContent('Following your vows, you may wish to make an additional promise to each other.'),
                $form->addRadioConditional('These promises are optional. Please choose from the list provided.', 'promise_option', [
                    $form->addRadioOption('We do not wish to add an additional promise', 'none'),
                    $form->addRadioConditionalOption('We have chosen promise number', 'promise_number', [
                        $form->addSelect('Select a promise number', 'promise_number', $form->getPromisesList())
                    ]),
                ]),
                $form->addCeremonyBookReference('View the promises for more information', FormBuilder::$TRADITIONAL_PROMISES_BOOK_URL),

            ]),
            $form->addSection('Step Five', 'Exchange of Rings', [
                $form->addTitle('Exchange of Rings'),
                $form->addRadioConditional('Who will be giving rings?', 'who_will_give_rings', [
                    $form->addRadioConditionalOption('We will both be giving rings', 'both', [
                        $form->addRadioConditional('If rings are being exchanged, would you like someone to present the ring(s)?', 'both_who_presents_rings', [
                            $form->addRadioOption('No', 'no'),
                            $form->addRadioConditionalOption('Yes', 'yes', [
                                $form->addText('What is the ring bearers name?', 'both_ring_bearers_name'),
                                $form->addText('What is their relationship to you?', 'both_ring_bearer_relationship', 'Ie. family member, friend etc.'),
                            ]),
                        ]),
                    ]),
                    $form->addRadioConditionalOption('{{partner_one_name}} will be giving a ring', 'partner_one', [
                        $form->addRadioConditional('If rings are being exchanged, would you like someone to present the ring(s)?', 'p_one_who_presents_rings', [
                            $form->addRadioOption('No', 'no'),
                            $form->addRadioConditionalOption('Yes', 'yes', [
                                $form->addText('What is the ring bearers name?', 'p_one_ring_bearers_name'),
                                $form->addText('What is their relationship to you?', 'p_one_ring_bearer_relationship', 'Ie. family member, friend etc.'),
                            ]),
                        ]),
                    ]),
                    $form->addRadioConditionalOption('{{partner_two_name}} will be giving a ring', 'partner_two', [

                        $form->addRadioConditional('If rings are being exchanged, would you like someone to present the ring(s)?', 'p_two_who_presents_rings', [
                            $form->addRadioOption('No', 'no'),
                            $form->addRadioConditionalOption('Yes', 'yes', [
                                $form->addText('What is the ring bearers name?', 'p_two_ring_bearers_name'),
                                $form->addText('What is their relationship to you?', 'p_two_ring_bearer_relationship', 'Ie. family member, friend etc.'),
                            ]),
                        ]),
                    ]),
                    $form->addRadioOption('Neither of us will be giving rings', 'none'),
                ]),
            ]),
            $form->addSection('Step Six', 'Readings', [
                $form->addTitle('Readings'),
                $form->addContent('You may wish to include one or two readings during your ceremony.  These are optional from the list provided'),
                $form->addRadioConditional('Please indicate which first reading option you would like', 'first_reading', [
                    $form->addRadioOption('We do not wish to have a reading', 'none'),
                    $form->addRadioConditionalOption('A reading from those provided', 'provided_reading', [
                        $form->addSelect('Please select a reading', 'provided_reading_one', $form->getReadingsList()),
                        $form->addText('Who will be reading your first reading?', 'reading_one_reader', 'The Celebrant would be happy to perform this duty for you if required'),
                    ]),
                ]),
                $form->addRadioConditional('Please indicate which second reading option you would like', 'second_reading', [
                    $form->addRadioOption('We do not wish to have a reading', 'none'),
                    $form->addRadioConditionalOption('A reading from those provided', 'provided_reading', [
                        $form->addSelect('Please select a reading', 'provided_reading_two', $form->getReadingsList()),
                        $form->addText('Who will be reading your second reading?', 'reading_two_reader', 'The Celebrant would be happy to perform this duty for you if required'),
                    ]),
                ]),
                $form->addCeremonyBookReference('View the readings for more information', FormBuilder::$TRADITIONAL_READINGS_BOOK_URL),

            ]),
            $form->addConditionalSections('Step Seven', 'Music', 'venue_type', [
                $form->addConditionalSection('registration_office', [
                    $form->addTitle('Music'),
                    $form->addRadioConditional('Please indicate which music option you would like', 'ro_music_option', [
                        $form->addRadioConditionalOption('Your own music (specify below)', 'own_music', [
                            $form->addContent('If you wish to choose your own music, please specify the title, artist, and if appropriate, the version of the tracks of your choice.'),
                            $form->addContent('We will set up your music choices on our digital music system so they are ready on the day of your ceremony.  If we have any problems with the tracks you have chosen we will contact you directly.'),
                            $form->addText('Whilst guests are assembling', 'ro_music_while_assembling'),
                            $form->addText('On entrance of bride/groom', 'ro_music_on_entrance'),
                            $form->addText('Whilst signing the schedule', 'ro_music_signing'),
                            $form->addText('Whilst departing', 'ro_music_on_departure'),
                        ]),
                        $form->addRadioOption('A classical selection provided by the registrar', 'registrar_classic'),
                        $form->addRadioOption('A modern selection provided by the registrar', 'registrar_modern'),
                    ])
                ]),
                $form->addConditionalSection('approved_venue', [
                    $form->addTitle('Music'),
                    $form->addContent('If you wish to choose your own music, please specify the title and artist.
Please Note: Music should be arranged and organised directly with the venue. Please make sure you discuss this with the venue and/or ceremony planner.'),
                    $form->addText('Whilst guests are assembling', 'av_music_while_assembling'),
                    $form->addText('On entrance of bride/groom', 'av_music_on_entrance'),
                    $form->addText('Whilst signing the schedule', 'av_music_signing'),
                    $form->addText('Whilst departing', 'av_music_on_departure'),
                ]),
            ]),
            $form->addSection('Step Eight', 'Guests and Witnesses', [
                $form->addTitle('Guests and Witnesses'),
                $form->addContent('Please note that this is the final step. Clicking the Submit Form button on this page will forward your ceremony choices onto us and you will no longer be able to edit it from this application.'),
                $form->addText('Number of Guests', 'number_of_guests'),
                $form->addSubtitle('Witnesses'),
                $form->addText('Name of witness 1', 'witness_1'),
                $form->addText('Name of witness 2', 'witness_2'),
                $form->addSubtitle('Special Mention'),
                $form->addTextarea('Is there anything you would like to mention to enable your day to run smoothly?', 'special_requests', '', false),
                $form->addSpecialCheck('I have completed my form and wish to submit it.', 'form_confirmation'),
            ])
        ]);

        return [
            'fields' => $form->getFieldsFlat(),
            'form'   => $form->form,
        ];
    }
}
