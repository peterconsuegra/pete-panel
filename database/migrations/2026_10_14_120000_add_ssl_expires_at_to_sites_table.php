<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the certificate a site serves expires: read after each issuance and by the daily
 * sites:renew-ssl, which renews it 30 days before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->timestamp('ssl_expires_at')
                ->nullable()
                ->after('ssl_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('ssl_expires_at');
        });
    }
};
