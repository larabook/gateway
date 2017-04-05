<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterIdInTransactionsTable extends Migration
{

	function getTable()
	{
		return config('gateway.table', 'gateway_transactions');
	}

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		\Illuminate\Support\Facades\DB::statement("update `" . $this->getTable() . "` set `payment_date`=null WHERE  `payment_date`=0;");
		\Illuminate\Support\Facades\DB::statement("ALTER TABLE `" . $this->getTable() . "` CHANGE `id` `id` BIGINT UNSIGNED NOT NULL;");
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		\Illuminate\Support\Facades\DB::statement("ALTER TABLE `" . $this->getTable() . "` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL;");
	}
}
