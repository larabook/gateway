<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePasargadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pasargad', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('terminalId');
            $table->integer('merchantId');
            $table->string('certificatePath');
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
        Schema::dropIfExists('pasargad');
    }
}
