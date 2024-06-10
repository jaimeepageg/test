<?php

use Phinx\Migration\AbstractMigration;

final class CreateBookingsTable extends AbstractMigration
{

	public function change() : void
	{
		// create the table
		$table = $this->table('wp_ceremonies_bookings');
		$table->addColumn('zip_reference', 'integer')
		      ->addColumn('email_address', 'string')
		      ->addColumn('office', 'string')
		      ->addColumn('type', 'string')
		      ->addColumn('booking_date', 'datetime')
		      ->addColumn('zip_notes', 'text')
		      ->addColumn('zip_related_bookings', 'string')
		      ->addColumn('zip_last_pull', 'datetime')
		      ->addColumn('booking_cost', 'integer')
		      ->addColumn('created_at', 'datetime')
		      ->addColumn('updated_at', 'datetime')
		      ->addColumn('raw_data', 'text')
		      ->create();
		$table->save();
	}

}
