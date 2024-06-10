<?php

namespace Ceremonies\Services\FormTemplates;

use Ceremonies\Services\FormBuilder;

class StatutoryCeremony implements FormTemplate {

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
				$form->addSubtitle( 'Basic Ceremony Details' ),
				$form->addSelect( 'Please indicate the parties to be married', 'who_is_getting_married', [
					'Bride and Groom',
					'Bride and Bride',
					'Groom and Groom'
				] ),
			] ),
			$form->addSection( 'Step Two', 'Initial Ceremony Details', [
				$form->addTitle( 'Initial Ceremony Details' ),
				$form->addSubtitle( 'Meeting the Registrar' ),
                $form->addContent('On the day of your marriage, before your ceremony begins, you will be seen by the registrar'),
				$form->addSubtitle( 'Couples Details' ),
				$form->addContent( 'Please enter the names fully, as you would like them read during the service.' ),
                $form->addText('{{partnerOneType}}\'s Full Name', 'partner_one_name'),
                $form->addText('{{partnerTwoType}}\'s Full Name', 'partner_two_name'),
				$form->addContent('Please note. Your full name as it appears on the schedule will be used during the declaratory and contracting vows'),
			]),

			$form->addSection( 'Step Three', 'Exchange of Rings', [
				$form->addTitle( 'Exchange of Rings' ),
				$form->addRadio( 'Who will be giving rings?', 'who_will_give_rings', [
					$form->addRadioOption( 'We will both be giving rings', 'both' ),
					$form->addRadioOption( '{{partner_one_name}} will be giving a ring', 'partner_one' ),
					$form->addRadioOption( '{{partner_two_name}} will be giving a ring', 'partner_two' ),
					$form->addRadioOption( 'Neither of us will be giving rings', 'none' ),
				] ),
			] ),
			$form->addSection( 'Step Four', 'Witnesses', [
                $form->addTitle('Witnesses'),
				$form->addContent( 'Please note that this is the final step. Clicking the Submit Form button on this page will forward your ceremony choices onto us and you will no longer be able to edit it from this application.' ),
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
