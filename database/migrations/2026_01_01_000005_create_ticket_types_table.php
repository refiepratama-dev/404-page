<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedInteger('quota')->default(0);
            $table->unsignedInteger('sold')->default(0);
            $table->timestamp('sales_start')->nullable();
            $table->timestamp('sales_end')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
