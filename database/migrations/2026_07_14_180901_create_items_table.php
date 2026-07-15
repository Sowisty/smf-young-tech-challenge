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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            // Powiązanie z tabelą invoices
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('name');           // Nazwa pozycji/usługi
            $table->integer('quantity');      // Ilość
            $table->decimal('price', 10, 2);  // Cena jednostkowa (np. 99.99)
            $table->decimal('total', 10, 2);  // Wartość łączna (ilość * cena)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
