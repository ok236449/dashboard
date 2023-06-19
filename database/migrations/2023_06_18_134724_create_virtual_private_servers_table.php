<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('virtual_private_servers', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->integer('user_id');
            $table->integer('price');
            $table->string('uuid');
            $table->timestamp('last_payment')->nullable();
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
        Schema::dropIfExists('virtual_private_servers');
    }
};
