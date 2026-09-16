<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'conversation_messages',
            function (Blueprint $table) {
                $table->foreignId('slack_user_id')
                    ->nullable()
                    ->after('conversation_id')
                    ->constrained('slack_users')
                    ->nullOnDelete();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'conversation_messages',
            function (Blueprint $table) {
                $table->dropConstrainedForeignId(
                    'slack_user_id'
                );
            }
        );
    }
};
