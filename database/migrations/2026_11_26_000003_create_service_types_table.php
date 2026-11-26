<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->unsignedInteger('default_interval_km')->nullable();
            $table->unsignedSmallInteger('default_interval_months')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('interval_km')->nullable();
            $table->unsignedSmallInteger('interval_months')->nullable();
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('last_service_record_id')->nullable();
            $table->unsignedInteger('next_due_km')->nullable();
            $table->date('next_due_date')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'service_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
        Schema::dropIfExists('service_types');
    }
};
