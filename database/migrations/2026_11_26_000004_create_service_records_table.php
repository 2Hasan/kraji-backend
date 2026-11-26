<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_type_id')->constrained()->restrictOnDelete();
            $table->date('service_date');
            $table->unsignedInteger('odometer');
            $table->decimal('cost', 12, 3)->nullable();
            $table->string('currency', 3)->default('BHD');
            $table->string('workshop_name')->nullable();
            $table->text('notes')->nullable();
            $table->string('source')->default('owner_entered');
            $table->string('status')->default('active'); // active | voided
            $table->string('void_reason')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('odometer_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('km');
            $table->timestamp('recorded_at');
            $table->string('source')->default('manual');
            $table->timestamps();
        });

        Schema::create('service_record_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_record_id')->constrained()->cascadeOnDelete();
            $table->string('storage_key');
            $table->string('mime_type');
            $table->unsignedInteger('size_bytes');
            $table->timestamp('redacted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_record_attachments');
        Schema::dropIfExists('odometer_readings');
        Schema::dropIfExists('service_records');
    }
};
