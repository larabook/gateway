<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMellatTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mellat', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('username');
            $table->string('password');
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
        Schema::dropIfExists('mellat');
    }
}
