<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPackages extends AbstractMigration
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
        $table = $this->table('wp_ceremonies_packages');
        $table->addColumn('user_id', 'integer', ['null' => false]);
        $table->addColumn('post_id', 'integer', ['null' => true]);
        $table->addColumn('type', 'string', ['limit' => 20, 'null' => false]);
        $table->addColumn('name', 'string', ['limit' => 200, 'null' => false]);
        $table->addColumn('expiryDate', 'datetime', ['null' => false]);
        $table->addColumn('total', 'string', ['limit' => 50, 'null' => false]);
        $table->addColumn('lastReminderSent', 'datetime', ['null' => true]);
        $table->addColumn('startDate', 'datetime', ['default' => 'CURRENT_TIMESTAMP', 'null' => false]);
        $table->addColumn('expiryNoticeSent', 'datetime', ['null' => true]);
        $table->create();
    }
}
