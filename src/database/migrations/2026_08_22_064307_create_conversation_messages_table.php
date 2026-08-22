<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'conversation_messages',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('conversation_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->uuid('message_id')
                    ->unique();

                $table->string('role', 20);

                $table->text('content');

                $table->timestampTz(
                    'message_created_at'
                );

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'conversation_messages'
        );
    }
};
