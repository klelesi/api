<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('author_id')->index();
            $table->unsignedTinyInteger('post_type');
            $table->string('slug')->unique();
            $table->text('title');
            $table->unsignedSmallInteger('number_of_comments')->default(0);
            $table->date('pinned_at')->nullable()->index();
            $table->date('pinned_until')->nullable()->index();
            $table->date('locked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
