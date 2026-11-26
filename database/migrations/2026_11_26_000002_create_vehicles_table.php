<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('chassis_no');
            $table->string('chassis_hash', 64);
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('trim')->nullable();
            $table->string('body_type')->nullable();
            $table->string('engine')->nullable();
            $table->string('transmission')->nullable();
            $table->string('color')->nullable();
            $table->string('plate_number')->nullable();
            $table->string('plate_style')->default('classic');
            $table->boolean('plate_visible')->default(true);
            $table->string('nickname')->nullable();
            $table->unsignedInteger('current_odometer')->default(0);
            $table->string('status')->default('active');
            $table->string('source')->default('manual');
            $table->timestamps();
        });

        // شاصي فريد فقط بين السيارات النشطة
        DB::statement("CREATE UNIQUE INDEX vehicles_active_chassis_unique ON vehicles (chassis_hash) WHERE status = 'active'");
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
