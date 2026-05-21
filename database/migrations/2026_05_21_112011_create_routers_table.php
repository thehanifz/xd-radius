<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->unsignedSmallInteger('api_port')->default(8728);
            $table->string('api_username');
            $table->text('api_secret');
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_connected_at')->nullable();
            $table->string('last_connection_status')->nullable();
            $table->string('routeros_version')->nullable();
            $table->text('last_connection_error')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routers');
    }
};
