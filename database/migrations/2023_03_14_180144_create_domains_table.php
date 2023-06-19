<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->uuid('server_id');
            $table->string('cf_id')->nullable();
            $table->string('status')->nullable();
            $table->string('subdomain_prefix')->nullable();
            $table->string('subdomain_suffix')->nullable();
            $table->string('domain')->nullable();
            $table->string('type');
            $table->string('target');
            $table->string('node_domain')->nullable();
            $table->integer('port');
            $table->boolean('bungee_active')->default(0);
            //$table->boolean('allow_direct_connection')->default(1);
            $table->boolean('show_on_lobby')->default(0);
            $table->string('bungee_token', 64)->unique()->default(DB::raw("SUBSTR(REPLACE(CONCAT(UUID(), UUID(), UUID()),'-',''),1,64)"));
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
        Schema::dropIfExists('domains');
    }
};
