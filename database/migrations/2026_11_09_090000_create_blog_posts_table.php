<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->nullable();          // free-text label, e.g. "Real Weddings"
            $table->string('excerpt', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('cover_alt')->nullable();
            $table->string('author')->nullable();
            $table->timestamp('published_at')->nullable();   // future date = scheduled
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
