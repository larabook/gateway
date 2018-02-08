<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterIdInTransactionsTable extends Migration
{

	function getTable()
	{
		return config('gateway.table', 'gateway_transactions');
	}

	function getLogTable()
	{
		return $this->getTable().'_logs';
	}


	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		try {
			DB::statement("ALTER TABLE  `" . $this->getLogTable() . "` drop foreign key transactions_logs_transaction_id_foreign;");
			DB::statement("ALTER TABLE  `" . $this->getLogTable() . "` DROP INDEX transactions_logs_transaction_id_foreign;");
		} catch (Exception $e) {
			
		}	
		
		try {		
			DB::statement("update `" . $this->getTable() . "` set `payment_date`=null WHERE  `payment_date`=0;");
			DB::statement("ALTER TABLE `" . $this->getTable() . "` CHANGE `id` `id` BIGINT UNSIGNED NOT NULL;");
			DB::statement("ALTER TABLE `" . $this->getLogTable() . "` CHANGE `transaction_id` `transaction_id` BIGINT UNSIGNED NOT NULL;");
			DB::statement("ALTER TABLE  `" . $this->getLogTable() . "` ADD INDEX `transactions_logs_transaction_id_foreign` (`transaction_id`);");
		} catch (Exception $e) {
			
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		// Don't check for foreign key constraints when executing below query in current session
		DB::statement('set foreign_key_checks=0');

		DB::statement("ALTER TABLE `" . $this->getTable() . "` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL;");
		
		// Ok! now DBMS can check for foregin key constraints
		DB::statement('set foreign_key_checks=1');

	}
}
