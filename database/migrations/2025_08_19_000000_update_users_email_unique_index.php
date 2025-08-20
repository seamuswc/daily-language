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
        Schema::table('users', function (Blueprint $table) {
            // Best-effort: drop old unique on email if it exists (name is framework default)
            try {
                $table->dropUnique('users_email_unique');
            } catch (\Throwable $e) {
                // Index might not exist on fresh installs; ignore
            }

            // Add composite unique on (email, language)
            try {
                $table->unique(['email', 'language'], 'users_email_language_unique');
            } catch (\Throwable $e) {
                // If it already exists, ignore
            }
        });
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


