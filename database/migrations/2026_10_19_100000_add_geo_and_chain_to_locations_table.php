<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Derived from the address (or reverse geocoded) so locations can be
            // grouped into city, state and chain pages.
            $table->string('city', 120)->nullable()->after('address');
            $table->string('state', 10)->nullable()->after('city');
            $table->string('country', 2)->nullable()->after('state');
            $table->string('chain', 60)->nullable()->after('country');

            $table->index(['status', 'country', 'state', 'city'], 'locations_status_geo_index');
            $table->index(['status', 'chain'], 'locations_status_chain_index');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex('locations_status_geo_index');
            $table->dropIndex('locations_status_chain_index');
            $table->dropColumn(['city', 'state', 'country', 'chain']);
        });
    }
};
