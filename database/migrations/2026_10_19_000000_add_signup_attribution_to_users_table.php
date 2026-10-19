<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // First-touch attribution captured on the visitor's first request
            // and written here when they register.
            $table->string('signup_source', 100)->nullable()->after('google_id');
            $table->string('signup_medium', 100)->nullable()->after('signup_source');
            $table->string('signup_campaign', 150)->nullable()->after('signup_medium');
            $table->string('signup_referrer', 255)->nullable()->after('signup_campaign');
            $table->string('signup_landing_page', 255)->nullable()->after('signup_referrer');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'signup_source',
                'signup_medium',
                'signup_campaign',
                'signup_referrer',
                'signup_landing_page',
            ]);
        });
    }
};
