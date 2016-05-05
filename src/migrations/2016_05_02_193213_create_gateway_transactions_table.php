<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Larabookir\Gateway\PortAbstract;
use Larabookir\Gateway\GatewayResolver;
use Larabookir\Gateway\Enum;

class CreateGatewayTransactionsTable extends Migration
{
	private $table = 'gateway_transactions';

	function getTable()
	{
		return config('gateway.db_tables.transactions', $this->table);
	}

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create($this->getTable(), function (Blueprint $table) {
			$table->engine = "innoDB";
			$table->increments('id');
			$table->enum('port', [
				Enum::MELLAT,
				Enum::JAHANPAY,
				Enum::PARSIAN,
				Enum::PAYLINE,
				Enum::SADAD,
				Enum::ZARINPAL,
			]);
			$table->decimal('price', 15, 2);
			$table->string('ref_id', 100);
			$table->string('tracking_code', 50)->nullable();
			$table->string('card_number', 50)->nullable();
			$table->enum('status', [
				Enum::TRANSACTION_INIT,
				Enum::TRANSACTION_SUCCEED,
				Enum::TRANSACTION_FAILED,
			])->default(Enum::TRANSACTION_INIT);
			$table->timestamp('payment_date')->nullable();

			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop($this->getTable());
	}
}
