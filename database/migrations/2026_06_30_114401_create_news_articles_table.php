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
        Schema::create('news_articles', function (Blueprint $table) {
           

            $table->id();

            $table->string('name')->nullable();
            $table->string('author')->nullable();
            $table->string('title');
            $table->string('slug')->unique();

            $table->longText('description')->nullable();

            $table->string('meta_title')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();

            $table->longText('top_content')->nullable();
            $table->string('top_heading')->nullable();

            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('category_name')->nullable();

            $table->string('bottom_heading')->nullable();
            $table->longText('bottom_content')->nullable();

            $table->string('image_banner')->nullable();

            $table->unsignedInteger('ratingcount')->default(0);
            $table->decimal('ratingvalue', 3, 2)->default(0);

            $table->string('heading')->nullable();
            $table->longText('about_news')->nullable();

            $table->longText('paragraph1')->nullable();
            $table->longText('paragraph2')->nullable();
            $table->longText('paragraph3')->nullable();
            $table->longText('paragraph4')->nullable();
            $table->longText('paragraph5')->nullable();
            $table->longText('paragraph6')->nullable();

            $table->text('faqq1')->nullable();
            $table->longText('faqa1')->nullable();

            $table->text('faqq2')->nullable();
            $table->longText('faqa2')->nullable();

            $table->text('faqq3')->nullable();
            $table->longText('faqa3')->nullable();

            $table->text('faqq4')->nullable();
            $table->longText('faqa4')->nullable();

            $table->text('faqq5')->nullable();
            $table->longText('faqa5')->nullable();

            $table->string('image')->nullable();

            $table->boolean('status')->default(true)->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
