<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTasksTable extends AbstractMigration
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

		$table = $this->table('wp_ceremonies_tasks');
		$table->addColumn('booking_id', 'integer')
            ->addColumn('zip_id', 'string', ['length' => 256, 'null' => true])
			->addColumn('name', 'string')
			->addColumn('completed_at', 'timestamp')
			->addColumn('complete_by', 'timestamp')
			->addColumn('note', 'text')
			->create();
		$table->save();

    }
}
