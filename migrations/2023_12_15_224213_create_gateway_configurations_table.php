<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionToGatewayTransactions extends Migration
{
    public function getTable()
    {
        return config('config_table', 'gateway_configurations');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->increments('id');

            //TODO Laravel 4 does not support enums
            // We must use following line in that case
            // DB::statement("ALTER TABLE gateway_configurations ADD port ENUM(".join(",",(array)Enum::getIPGs()).");
            $table->enum('port', (array)Enum::getIPGs());

            // Since Multiple settings can be applied to port other than main configuration
            // there must be a key-value pair system in db.
            //the main configuration key to load the gateway connectiong settings is : "main"
            $table->string('key');
            $table->text('value');

            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate("cascade");
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
