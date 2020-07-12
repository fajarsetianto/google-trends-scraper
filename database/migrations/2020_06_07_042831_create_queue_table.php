<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queue', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('status')->default(1);
            $table->longText('data');
            $table->longText('corelation')->nullable();
            $table->integer('category');
            $table->longText('keywords');
            $table->longText('fetched_keywords')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();
        });

        /*
        status detail 
        0 => fail
        1 => add to queue
        2 => fetching data
        3 => Normalization
        4 => finish
        */
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('queue');
    }
}
