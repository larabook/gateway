<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAsanpardakhtTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asanpardakht', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('merchantId');
            $table->integer('merchantConfigId');
            $table->string('username');
            $table->string('password');
            $table->string('key');
            $table->string('iv');
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
        Schema::dropIfExists('asanpardakht');
    }
}
