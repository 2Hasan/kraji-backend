<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_admin_id'); // بدون FK حتى ما يتغيّر السجل لو حُذف الحساب
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->text('note')->nullable();
            $table->timestamp('created_at');
        });

        // ما يُعدَّل ولا يُحذف أي صف، حتى من قاعدة البيانات مباشرة
        DB::statement(<<<'SQL'
            CREATE OR REPLACE FUNCTION audit_logs_block_changes() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'audit_logs is append-only';
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER audit_logs_no_update_delete
            BEFORE UPDATE OR DELETE ON audit_logs
            FOR EACH ROW EXECUTE FUNCTION audit_logs_block_changes();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS audit_logs');
        DB::statement('DROP FUNCTION IF EXISTS audit_logs_block_changes()');
    }
};
