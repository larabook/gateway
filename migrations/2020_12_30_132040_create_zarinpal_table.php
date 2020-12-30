<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZarinpalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zarinpal', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('merchantId');
            $table->string('type');
            $table->string('callbackUrl');
            $table->string('server');
            $table->string('email');
            $table->integer('mobile');
            $table->text('description');
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
        Schema::dropIfExists('zarinpal');
    }
}
