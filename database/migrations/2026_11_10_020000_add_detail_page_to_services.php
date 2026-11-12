<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->json('gallery')->nullable();      // [{image,title,alt}]
            $table->json('videos')->nullable();       // [{title,description,source,url,file,thumbnail}]
            $table->json('highlights')->nullable();   // [{text}]  "what's included"
            $table->json('faqs')->nullable();         // [{question,answer}]
            $table->string('seo_title', 160)->nullable();
            $table->string('seo_description', 320)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['gallery', 'videos', 'highlights', 'faqs', 'seo_title', 'seo_description']);
        });
    }
};
