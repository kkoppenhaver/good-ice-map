<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('place_id')->nullable()->unique()->after('id');
            $table->enum('source', ['user_submitted', 'scraped'])->default('user_submitted')->after('status');
            $table->timestamp('scraped_at')->nullable()->after('source');
            $table->integer('ice_score')->nullable()->after('total_ratings');
            $table->string('business_type')->nullable()->after('ice_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['place_id', 'source', 'scraped_at', 'ice_score', 'business_type']);
        });
    }
};
