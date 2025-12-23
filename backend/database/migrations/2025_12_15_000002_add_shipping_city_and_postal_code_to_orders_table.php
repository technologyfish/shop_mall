<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShippingCityAndPostalCodeToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Add shipping_city column after shipping_address
            $table->string('shipping_city', 100)->nullable()->after('shipping_address');
            // Add shipping_postal_code column after shipping_city
            $table->string('shipping_postal_code', 20)->nullable()->after('shipping_city');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_city', 'shipping_postal_code']);
        });
    }
}





