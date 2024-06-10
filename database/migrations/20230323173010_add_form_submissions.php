<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Although it may not be best practice to have multiple
 * tables in a single migration file, the plugin already
 * had several tables setup before migrations were added.
 */
final class AddFormSubmissions extends AbstractMigration
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
        $table = $this->table('wp_ceremonies_form_submissions');
        $table->addColumn('form', 'string', ['limit' => 100, 'null' => false]);
        $table->addColumn('data', 'text', ['null' => false]);
        $table->addColumn('sent_to', 'string', ['limit' => 400, 'null' => false]);
        $table->addColumn('email_sent', 'boolean', ['default' => 0, 'null' => false]);
        $table->addColumn('created_at', 'datetime', ['null' => false]);
        $table->addColumn('updated_at', 'datetime', ['null' => false]);
        $table->create();
    }
}
