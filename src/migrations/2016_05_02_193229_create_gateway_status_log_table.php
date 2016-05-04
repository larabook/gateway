<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGatewayStatusLogTable extends Migration
{
    private $table = 'gateway_status_log';

    function getTable()
    {
        return config('gateway.db_tables.logs',$this->table);
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->engine="innoDB";
            $table->increments('id');
            $table->unsignedInteger('transaction_id');
            $table->string('result_code', 10)->nullable();
            $table->string('result_message', 255)->nullable();
            $table->timestamp('log_date')->nullable();

            $table
                ->foreign('transaction_id')
                ->references('id')
                ->on('gateway_transactions')
                ->onDelete('cascade');
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
