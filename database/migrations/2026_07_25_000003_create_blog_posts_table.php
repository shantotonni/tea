<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlogPostsTable extends Migration
{
    public function up()
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default('garden'); // brewing | health | garden
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->text('excerpt');
            $table->string('image')->nullable();
            $table->string('author')->nullable();
            $table->string('role')->nullable();
            $table->string('read_time')->default('4 min read');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->date('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('blog_posts');
    }
}
