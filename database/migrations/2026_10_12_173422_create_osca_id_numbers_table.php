<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osca_id_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('issued_year');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('osca_id_numbers');
    }
};
