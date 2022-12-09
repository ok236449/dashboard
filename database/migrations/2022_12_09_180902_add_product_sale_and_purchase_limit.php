<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductSaleAndPurchaseLimit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('on_sale') ->default(false);
            $table->integer('max_servers_per_user')->default(0);
            $table->tinyText('custom_ribbon_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('on_sale');
            $table->dropColumn('max_servers_per_user');
            $table->dropColumn('custom_ribbon_text');
        });
    }
}
