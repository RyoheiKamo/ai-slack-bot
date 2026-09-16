<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slack_users', function (Blueprint $table) {
            $table->id();

            $table->string('slack_user_id')->unique();

            $table->string('display_name')->nullable();

            $table->string('real_name')->nullable();

            $table->timestampTz('first_used_at')->nullable();

            $table->timestampTz('last_used_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slack_users');
    }
};
