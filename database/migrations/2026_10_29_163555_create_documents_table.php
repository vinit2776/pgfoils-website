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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('fiscal_year', 9)->nullable()->index();
            $table->string('period')->nullable();
            $table->date('doc_date')->nullable();
            $table->string('status', 12)->default('draft')->index();
            $table->timestamp('publish_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('file_path')->nullable();
            $table->string('external_url', 1000)->nullable();
            $table->string('original_name')->nullable();
            $table->string('file_type', 8)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 64)->nullable()->index();
            $table->date('last_updated_on_site')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
