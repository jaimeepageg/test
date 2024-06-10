<?php

namespace Ceremonies\Services\FormTemplates;

use Ceremonies\Services\FormBuilder;

class EnhancedCeremony implements FormTemplate
{

    /**
     * @inheritDoc
     */
    public static function generate(): array
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
                                $form->addText('Who will perform this duty?', 'person_accompanying_partner_one'),
                            ]),
                            $form->addRadioConditionalOption('{{partner_two_name}} wishes to be accompanied', '{{partner_two_name}} to be accompanied', [
                                $form->addText('Who will perform this duty?', 'person_accompanying_partner_two'),
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
                $form->addTitle('Additional Promise'),
                $form->addContent('Following your vows, you may wish to make an additional promise to each other.'),
                $form->addRadioConditional('These promises are optional. Please choose from the list provided.', 'promise_option_jh', [
                    $form->addRadioOption('We do not wish to add an additional promise', 'none'),
                    $form->addRadioConditionalOption('We have chosen promise number', 'declarations_promise_number', [
                        $form->addSelect('Select a promise number', 'declarations_promise_number', $form->getPromisesList()),
                        $form->addCeremonyBookReference('View the promises for more information', FormBuilder::$ENHANCED_PROMISES_BOOK_URL),
                    ]),
                    $form->addRadioConditionalOption('We will provide our own promises', 'declarations_promise_uploaded', [
                        $form->addFile('Please upload your promises', 'declarations_promise_uploaded'),
                    ]),
                ]),
            ]),
            $form->addSection('Step Four', 'Exchange of Rings', [
                $form->addTitle('Exchange of Rings'),
                $form->addRadioConditional('Who will be giving rings?', 'who_will_give_rings', [
                    $form->addRadioConditionalOption('We will both be giving rings', 'both', [
                        $form->addContent('When exchanging rings, you may choose the accompanying words. You can choose from our list or provide your own.'),

                        $form->addRadioConditional('Which ring promise would you like?', 'rings_both_ring_wording', [
                            $form->addRadioConditionalOption('From the selection provided', 'book_reading', [
                                $form->addSelect('Promise Number', 'rings_both_reading_number', $form->getRingPromises())
                            ]),
                            $form->addRadioConditionalOption('Our own ring promise', 'uploaded_reading', [
                                $form->addFile('Please attach your promise', 'rings_both_reading_file'),
                            ]),
                        ]),
                        $form->addRadioConditional('If rings are being exchanged, would you like someone to present the ring(s)?', 'rings_both_who_presents_rings', [
                            $form->addRadioOption('No', 'no'),
                            $form->addRadioConditionalOption('Yes', 'yes', [
                                $form->addText('What is the ring bearers name?', 'rings_both_ring_bearers_name'),
                                $form->addText('What is their relationship to you?', 'rings_both_ring_bearer_relationship', 'Ie. Family member, friend etc.'),
                            ]),
                        ]),
                    ]),
                    $form->addRadioConditionalOption('{{partner_one_name}} will be giving a ring', 'partner_one', [
                        $form->addContent('When exchanging rings, you may choose the accompanying words. You can choose from our list or provide your own.'),
                        $form->addCeremonyBookReference('See our ceremony book for more information', FormBuilder::$ENHANCED_BOOK_URL),
                        $form->addRadioConditional('Which ring promise would you like?', 'rings_prtnr_one_ring_wording', [
                            $form->addRadioConditionalOption('From the selection provided', 'book_reading', [
                                $form->addSelect('Promise Number', 'rings_prtnr_one_reading_number', $form->getRingPromises())
                            ]),
                            $form->addRadioConditionalOption('Our own ring promise', 'uploaded_reading', [
                                $form->addFile('Please attach your reading', 'rings_prtnr_one_reading_file'),
                            ]),
                        ]),
                        $form->addRadioConditional('If rings are being exchanged, would you like someone to present the ring(s)?', 'rings_prtnr_one_who_presents_rings', [
                            $form->addRadioOption('No', 'no'),
                            $form->addRadioConditionalOption('Yes', 'yes', [
                                $form->addText('What is the ring bearers name?', 'rings_prtnr_one_ring_bearers_name'),
                                $form->addText('What is their relationship to you?', 'rings_prtnr_onering_bearer_relationship', 'Ie. Family member, friend etc.'),
                            ]),
                        ]),
                    ]),
                    $form->addRadioConditionalOption('{{partner_two_name}} will be giving a ring', 'partner_two', [
                        $form->addContent('When exchanging rings, you may choose the accompanying words. You can choose from our list or provide your own.'),
                        $form->addCeremonyBookReference('See our ceremony book for more information', FormBuilder::$ENHANCED_BOOK_URL),
                        $form->addRadioConditional('Which ring promise would you like?', 'rings_prtnr_two_ring_wording', [
                            $form->addRadioConditionalOption('From the selection provided', 'book_reading', [
                                $form->addSelect('Please select a reading', 'rings_prtnr_two_reading_number', $form->getRingPromises())
                            ]),
                            $form->addRadioConditionalOption('Our own ring promise', 'rings_prtnr_two_uploaded_reading', [
                                $form->addFile('Please attach your reading', 'rings_prtnr_two_reading_file'),
                            ]),
                        ]),
                        $form->addRadioConditional('If rings are being exchanged, would you like someone to present the ring(s)?', 'rings_prtnr_two_who_presents_rings', [
                            $form->addRadioOption('No', 'no'),
                            $form->addRadioConditionalOption('Yes', 'yes', [
                                $form->addText('What is the ring bearers name?', 'rings_prtnr_two_ring_bearers_name'),
                                $form->addText('What is their relationship to you?', 'rings_prtnr_two_ring_bearer_relationship', 'Ie. Family member, friend etc.'),
                            ]),
                        ]),
                    ]),
                    $form->addRadioOption('Neither of us will be giving rings', 'none'),
                ]),
            ]),
            $form->addSection('Step Five', 'Joining of Hands Ceremony and Sand Ceremony', [
                $form->addTitle('Joining of Hands Ceremony and Sand Ceremony'),
                $form->addContent('With our enhanced ceremony package you can include either a joining of hands or a sand ceremony.'),
                $form->addSubtitle('Joining of Hands Ceremony'),
                $form->addContent('Joining of Hands Ceremonies can provide a modern quirky addition to the traditional ceremony and becoming increasingly popular. It shows the joining of two people by the holding of hands. How you hold your hands is your choice, there is no right or wrong way. You may wish for your hands to be ‘tied’ with ribbons or cords of your choice. Each of you have your chosen material and we use these to place them over your hands. We will then wrap them to create a loose knot, that the two of you will complete. The colours that you choose is completely up to you.'),
                $form->addSubtitle('Sand Ceremony'),
                $form->addContent('There will be three glass vessels in total. Two of them will be the same size, and each of them will be filled with sand, (a different colour in each one) to represent each of you. There will also be a larger glass bottle and each of you will take it in turns to pour your sand into it. This will form a beautiful design, created by the two of you. This is a representation as two become one.'),
                $form->addRadio('Which option would you prefer for your ceremony?', 'sand_or_joining_hands', [
                    ['label' => 'We have chosen a joining of hands ceremony', 'value' => 'joining_hands'],
                    ['label' => 'We have chosen a sand ceremony', 'value' => 'sand_ceremony'],
                    ['label' => 'We do not wish to include this element in our ceremony', 'value' => 'none']
                ]),
            ]),
            $form->addConditionalSections('Step Six', 'Sand Ceremony & Joining of Hands Ceremony', 'sand_or_joining_hands', [
                $form->addConditionalSection('sand_ceremony', [
                    $form->addTitle('Sand Ceremony'),
                    $form->addContent('There will be three glass vessels in total. Two of them will be the same size, and each of them will be filled with sand, (a different colour in each one) to represent each of you. There will also be a larger glass bottle and each of you will take it in turns to pour your sand into it. This will form a beautiful design, created by the two of you. This is a representation as two become one.'),
                    $form->addSubtitle('Sand & Glassware'),
                    $form->addRadio('Please indicate from the following', 'sand_and_glassware', [
                        $form->addRadioOption('We will provide our own sand & glassware', 'we_provide'),
                        $form->addRadioOption('Please provide sand & glassware for us', 'you_provide'),
                    ]),
                ]),
                $form->addConditionalSection('joining_hands', [
                    $form->addTitle('Joining Hands Ceremony'),
                    $form->addContent('Joining of Hands Ceremonies can provide a modern quirky addition to the traditional ceremony and becoming increasingly popular. It shows the joining of two people by the holding of hands. How you hold your hands is your choice, there is no right or wrong way. You may wish for your hands to be ‘tied’ with ribbons or cords of your choice. Each of you have your chosen material and we use these to place them over your hands. We will then wrap them to create a loose knot, that the two of you will complete. The colours that you choose is completely up to you.'),
                    $form->addSubtitle('Ribbons and Cords'),
                    $form->addContent('
	                    - You will need to provide the ribbon/cord<br/>
	                    - A minimum of 2 pieces are required<br/>
	                    - Please ensure your ribbon/cord is a minimum of 1 metre each<br/>
	                    - We will guide you through the process<br/>
	                '),
                    $form->addSubtitle('Accompanying Words'),
                    $form->addContent('You can choose the words that we read whilst you join hands from the choices provided, making it personal to you.'),
                    $form->addRadioConditional('Please choose from the list provided.', 'promise_option', [
                        $form->addRadioConditionalOption('We have chosen a reading from the list', 'jh_promise_number', [
                            $form->addSelect('Please select a reading', 'jh_promise_number', $form->getReadingsList())
                        ]),
                        $form->addRadioConditionalOption('We will provide our own readings', 'jh_promise_uploaded', [
                            $form->addFile('Please upload your readings', 'jh_promise_uploaded'),
                        ]),
                    ]),
                    $form->addCeremonyBookReference('View the readings for more information', FormBuilder::$ENHANCED_READINGS_BOOK_URL),

                ])
            ]),
            $form->addSection('Step Seven', 'Readings', [
                $form->addTitle('Readings'),
                $form->addContent('You may wish to include one or two readings during your ceremony. These are optional from the list provided'),
                $form->addRadioConditional('Please indicate which first reading option you would like', 'first_reading', [
                    $form->addRadioOption('We do not wish to have a reading', 'none'),
                    $form->addRadioConditionalOption('A reading from those provided', 'provided_reading', [
                        $form->addSelect('Please select a reading', 'provided_reading_one', $form->getReadingsList()),
                        $form->addText('Who will be reading your first reading?', 'provided_reading_reading_one_reader', 'The Celebrant would be happy to perform this duty for you if required'),
                    ]),
                    $form->addRadioConditionalOption('We will attach a file containing our own reading', 'reading_one_uploaded', [
                        $form->addFile('Please upload any required readings for your ceremony', 'ceremony_reading_one'),
                        $form->addText('Who will be reading your first reading?', 'uploaded_reading_reading_one_reader', 'The Celebrant would be happy to perform this duty for you if required'),
                    ]),
                ]),
                $form->addRadioConditional('Please indicate which second reading option you would like', 'second_reading', [
                    $form->addRadioOption('We do not wish to have a reading', 'none'),
                    $form->addRadioConditionalOption('A reading from those provided', 'provided_reading', [
                        $form->addSelect('Please select a reading', 'provided_reading_two', $form->getReadingsList()),
                        $form->addText('Who will be reading your second reading?', 'provided_reading_reading_two_reader', 'The Celebrant would be happy to perform this duty for you if required'),
                    ]),
                    $form->addRadioConditionalOption('We will attach a file containing our own reading', 'reading_two_uploaded', [
                        $form->addFile('Please upload any required readings for your ceremony', 'ceremony_reading_two'),
                        $form->addText('Who will be reading your second reading?', 'uploaded_reading_reading_two_reader', 'The Celebrant would be happy to perform this duty for you if required'),
                    ]),
                ]),
                $form->addCeremonyBookReference('View the readings for more information', FormBuilder::$ENHANCED_READINGS_BOOK_URL),
                ]),
            $form->addConditionalSections('Step Eight', 'Music', 'venue_type', [
                $form->addConditionalSection('registration_office', [
                    $form->addTitle('Music'),
                    $form->addRadioConditional('Please indicate which music option you would like', 'ro_music_option', [
                        $form->addRadioConditionalOption('Your own music (specify below)', 'own_music', [
                            $form->addContent('If you wish to choose your own music, please specify the title, artist, and if appropriate, the version of the tracks of your choice.'),
                            $form->addContent('If we have any problems with the tracks you have chosen we will contact you directly.'),
                            $form->addText('Whilst guests are assembling', 'ro_music_while_assembling'),
                            $form->addText('On entrance of bride/groom', 'ro_music_on_entrance'),
                            $form->addText('Whilst signing the schedule', 'ro_music_signing'),
                            $form->addText('Whilst departing', 'ro_music_on_departure'),
                        ]),
                        $form->addRadioOption('A classical selection provided by the registrar', 'registrar_classic'),
                        $form->addRadioOption('A modern selection provided by the registrar', 'registrar_modern'),
                        $form->addRadioConditionalOption('Other music choice (For example, artist or band)', 'music_other_choice', [
                            $form->addText('Details about other music choice', 'ro_music_other_choice', 'This will need to be arranged with the registration office. The registrars may need to contact you about the details.'),
                            $form->addContent('If you wish to choose your own music, please specify the title, artist, and if appropriate, the version of the tracks of your choice.'),
                            $form->addContent('If we have any problems with the tracks you have chosen we will contact you directly.'),
                            $form->addText('Whilst guests are assembling', 'ro_other_music_while_assembling'),
                            $form->addText('On entrance of bride/groom', 'ro_other_music_on_entrance'),
                            $form->addText('Whilst signing the schedule', 'ro_other_music_signing'),
                            $form->addText('Whilst departing', 'ro_other_music_on_departure'),
                        ])
                    ])
                ]),
                $form->addConditionalSection('approved_venue', [
                    $form->addTitle('Music'),
                    $form->addRadioConditional('Please indicate which music option you would like', 'av_music_option', [
                        $form->addRadioConditionalOption('Your own music (specify below)', 'own_music', [
                            $form->addContent('If you wish to choose your own music, please specify the title and artist. Please Note: Music should be arranged and organised directly with the venue. Please make sure you discuss this with the venue.'),
                            $form->addText('Whilst guests are assembling', 'av_own_music_while_assembling'),
                            $form->addText('On entrance of bride/groom', 'av_own_music_on_entrance'),
                            $form->addText('Whilst signing the schedule', 'av_own_music_signing'),
                            $form->addText('Whilst departing', 'av_own_music_on_departure'),
                        ]),
                        $form->addRadioConditionalOption('Other music choice (For example, artist or band)', 'music_other_choice', [
                            $form->addText('Details about other music choice', 'av_music_other_choice', 'This will need to be arranged with the registration office. The registrars may need to contact you about the details.'),
                            $form->addContent('Please specify the title and artist of the songs you would like...'),
                            $form->addText('Whilst guests are assembling', 'av_other_music_while_assembling'),
                            $form->addText('On entrance of bride/groom', 'av_other_music_on_entrance'),
                            $form->addText('Whilst signing the schedule', 'av_other_music_signing'),
                            $form->addText('Whilst departing', 'av_other_music_on_departure'),
                        ])
                    ])
                ]),
            ]),
            $form->addSection('Step Nine', 'Guests and Witnesses', [
                $form->addTitle('Guests and Witnesses'),
                $form->addContent('Please note that this is the final step. Clicking the Submit Form button on this page will forward your ceremony choices onto us and you will no longer be able to edit it from this application.'),
                $form->addSubtitle('Guests'),
                $form->addText('Number of Guests', 'number_of_guests', 'Please ensure you check the maximum capacity for the Venue as this includes guests, staff, photographers and couple to be married'),
                $form->addSubtitle('Witnesses'),
                $form->addText('Name of witness 1', 'witness_1'),
                $form->addText('Name of witness 2', 'witness_2'),
                $form->addText('Name of witness 3', 'witness_3', '', false),
                $form->addText('Name of witness 4', 'witness_4', '', false),
                $form->addSubtitle('Special Mention'),
                $form->addTextarea('Please inform us of anything additional we may need to know, or any special requests, that would help your day run smoothly.', 'special_requests', '', false),
                $form->addSpecialCheck('I have completed my form and wish to submit it.', 'form_confirmation'),
            ])
        ]);

        return [
            'fields' => $form->getFieldsFlat(),
            'form'   => $form->form,
        ];
    }
}
