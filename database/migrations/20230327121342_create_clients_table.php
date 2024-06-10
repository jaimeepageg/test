<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateClientsTable extends AbstractMigration
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

		$table = $this->table('wp_ceremonies_clients');
		$table->addColumn('first_name', 'string')
			->addColumn('last_name', 'string')
			->addColumn('email', 'string')
			->addColumn('phone', 'string')
			->addColumn('is_primary','boolean')
            ->addColumn('zip_id', 'string', ['length' => 256, 'null' => false])
			->addColumn('booking_id', 'integer')
			->create();
		$table->save();

    }
}
