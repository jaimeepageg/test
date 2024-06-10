<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddBookingPaymentsTable extends AbstractMigration
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
        $table = $this->table('wp_ceremonies_booking_payments');
        $table->addColumn('booking_id', 'integer', ['null' => false])
            ->addColumn('zip_id', 'string', ['length' => 256, 'null' => false])
            ->addColumn('item_name', 'text', ['null' => false])
            ->addColumn('status', 'string', ['null' => false])
            ->addColumn('amount', 'string', ['null' => false])
            ->create();
    }
}
