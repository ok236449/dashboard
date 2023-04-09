<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayerLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('player_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('online_players')->default(0);
            $table->integer('player_slots')->default(0);
            $table->decimal('average_players', 8, 2)->default(0);
            $table->integer('total_servers')->default(0);
            $table->json('raw_servers');
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
        Schema::dropIfExists('player_logs');
    }
}
