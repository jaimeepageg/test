<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateChoicesAnswersTable extends AbstractMigration
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

		$table = $this->table('wp_ceremonies_choices_questions');
		$table->addColumn('form_id', 'integer', ['null' => true])
			->addColumn('name', 'string')
			->addColumn('question', 'string')
			->addColumn('answer', 'text')
			->addColumn('position', 'integer')
			->create();

		// Add after table creation
	    /* $table->addForeignKey('form_id', 'wp_ceremonies_choices', 'id', ['delete'=> 'SET_NULL', 'update'=> 'NO_ACTION'])
		    ->save(); */

    }
}
