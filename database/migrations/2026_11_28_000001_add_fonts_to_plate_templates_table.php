<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plate_templates', function (Blueprint $table) {
            $table->json('fonts')->nullable()->after('svg_storage_key');
        });
    }

    public function down(): void
    {
        Schema::table('plate_templates', function (Blueprint $table) {
            $table->dropColumn('fonts');
        });
    }
};
