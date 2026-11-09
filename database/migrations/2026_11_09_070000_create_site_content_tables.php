<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('headers', function (Blueprint $table) {
            $table->id();
            $table->string('logo_path')->nullable();
            $table->string('logo_alt')->default('Magic Frames');
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_sticky')->default(true);
            // solid: white (original look) | cream: site cream colour | transparent_on_top: see-through over the banner until scrolled
            $table->string('background_style', 24)->default('solid');
            $table->boolean('show_cta')->default(true);
            $table->string('cta_label')->default('RSVP');
            $table->string('cta_url')->default('/contact');
            $table->boolean('show_floating_button')->default(true);
            $table->boolean('show_social_in_mobile_menu')->default(true);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            // layout used by the public site: default, home, about, services, portfolio, gallery, videos, blog, contact, shop
            $table->string('template', 24)->default('default');
            $table->boolean('is_system')->default(false);
            $table->string('banner_label')->nullable();
            $table->string('banner_title')->nullable();
            $table->string('banner_image_path')->nullable();
            $table->longText('content')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            // text | image_text | image | video | cta
            $table->string('type', 24)->default('text');
            $table->string('label')->nullable();
            $table->string('heading')->nullable();
            $table->longText('body')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('video_url')->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->boolean('image_right')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            // header_left | header_right | mobile_only | footer
            $table->string('location', 24);
            $table->string('label');
            $table->foreignId('page_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url')->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['location', 'is_visible', 'sort_order']);
        });

        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('og_image_path')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();

            $table->unique(['seoable_type', 'seoable_id']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();           // Font Awesome class e.g. "fa-solid fa-camera"
            $table->string('short_description', 400)->nullable();
            $table->longText('description')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('client_role')->nullable();     // e.g. "Bride", event type
            $table->string('photo_path')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->text('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('heading');
            $table->string('subheading')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();      // hero, about, quote, services, stats, portfolio, videos, recent, testimonials, social, contact
            $table->string('admin_label');
            $table->string('label')->nullable();      // small script label above the title
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->text('body_secondary')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_secondary_path')->nullable();
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->unsignedInteger('items_limit')->default(6);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('statistics', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->unsignedBigInteger('value')->default(0);
            $table->string('suffix', 8)->nullable();   // "+", "%", ...
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['statistics', 'home_sections', 'hero_slides', 'testimonials', 'services', 'seo_metadata', 'navigation_menus', 'page_sections', 'pages', 'headers', 'website_settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
