<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPayments extends AbstractMigration
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
        $table = $this->table('wp_ceremonies_payments');
        $table->addColumn('userId', 'integer', ['null' => true]);
        $table->addColumn('requestId', 'integer', ['null' => false]);
        $table->addColumn('packageId', 'integer', ['null' => true]);
        $table->addColumn('bookingId', 'integer', ['null' => true]);
        $table->addColumn('packageName', 'string', ['limit' => 200, 'null' => false]);
        $table->addColumn('amountPaid', 'string', ['limit' => 30, 'null' => true]);
        $table->addColumn('scpReference', 'string', ['limit' => 300, 'null' => false]);
        $table->addColumn('state', 'string', ['limit' => 100, 'null' => true]);
        $table->addColumn('transactionId', 'string', ['limit' => 100, 'null' => true]);
        $table->addColumn('cardNumber', 'string', ['limit' => 20, 'null' => true]);
        $table->addColumn('cardType', 'string', ['limit' => 100, 'null' => true]);
        $table->addColumn('created_at', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => true]);
        $table->addColumn('updated_at', 'datetime', ['null' => true, 'update' => 'CURRENT_TIMESTAMP']);
        $table->create();
    }
}
