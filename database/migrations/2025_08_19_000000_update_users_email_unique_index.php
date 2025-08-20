<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old unique index safely across drivers
        try {
            // SQLite, Postgres support IF EXISTS; MySQL ignores schema name here
            DB::statement('DROP INDEX IF EXISTS users_email_unique');
        } catch (\Throwable $e) {
            // As a fallback, attempt schema drop and ignore errors
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique('users_email_unique');
                });
            } catch (\Throwable $inner) {
                // ignore
            }
        }

        // Add composite unique on (email, language)
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique(['email', 'language'], 'users_email_language_unique');
            });
        } catch (\Throwable $e) {
            // ignore if already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop composite unique if present
            try {
                $table->dropUnique('users_email_language_unique');
            } catch (\Throwable $e) {
                // Ignore if missing
            }

            // Restore unique on email
            try {
                $table->unique('email', 'users_email_unique');
            } catch (\Throwable $e) {
                // Ignore if cannot recreate
            }
        });
    }
};


