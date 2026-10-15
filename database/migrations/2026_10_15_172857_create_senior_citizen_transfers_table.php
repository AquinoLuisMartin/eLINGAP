<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('senior_citizen_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('senior_citizen_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->string('requested_by', 10);
            $table->string('requester_name', 350);
            $table->string('requester_relationship', 100)->nullable();
            $table->text('previous_location');
            $table->string('destination_municipality', 100);
            $table->string('destination_province', 100);
            $table->string('destination_barangay', 100);
            $table->text('destination_address');
            $table->date('transferred_on');
            $table->text('reason')->nullable();
            $table->string('senior_name', 350);
            $table->string('registration_number', 30);
            $table->string('osca_id_number', 50)->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['senior_citizen_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('senior_citizen_transfers');
    }
};
