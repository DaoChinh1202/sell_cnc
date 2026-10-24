<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('file_format', 100)->nullable();
            $table->string('compatible_software')->nullable();
            $table->string('file_size', 100)->nullable();
            $table->string('design_dimensions')->nullable();
            $table->text('download_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['file_format', 'compatible_software', 'file_size', 'design_dimensions', 'download_note']);
        });
    }
};
