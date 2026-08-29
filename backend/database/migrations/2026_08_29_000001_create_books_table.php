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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('isbn_13')->nullable()->unique();
            $table->string('isbn_10')->nullable()->unique();
            $table->date('published_date')->nullable();
            $table->integer('page_count')->nullable();
            $table->string('language')->nullable();
            $table->string('external_id')->nullable()->unique();
            $table->string('source')->default('open_library');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
