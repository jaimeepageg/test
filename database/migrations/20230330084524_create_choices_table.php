<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateChoicesTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {

		$table = $this->table('wp_ceremonies_choices');
		$table->addColumn('booking_id', 'integer', ['null' => true])
			->addColumn('form_name', 'string')
			->addColumn('status', 'string')
			->addColumn('created_at', 'datetime')
			->addColumn('updated_at', 'datetime')
			->addColumn('notes', 'text')
			->create();

		/* $table->addForeignKey(
			'booking_id',
			'wp_ceremonies_bookings',
			'id',
			['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION']
		);*/

		$table->save();

    }
}
