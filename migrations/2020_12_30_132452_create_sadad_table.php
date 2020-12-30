<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSadadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sadad', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('merchant');
            $table->string('transactionKey');
            $table->integer('terminalId');
            $table->string('callbackUrl');
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
        Schema::dropIfExists('sadad');
    }
}
