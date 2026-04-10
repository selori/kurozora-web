<?php

use App\Models\MediaRating;
use App\Models\RatingCategory;
use App\Models\RatingCategoryScore;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create(RatingCategoryScore::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('media_rating_id');
            $table->unsignedBigInteger('rating_category_id');
            $table->double('score')->default(RatingCategoryScore::MAX_SCORE_VALUE);
            $table->timestamps();
        });

        Schema::table(RatingCategoryScore::TABLE_NAME, function (Blueprint $table) {
            // Set unique key constraints
            $table->unique(['media_rating_id', 'rating_category_id']);

            // Set foreign key constraints
            $table->foreign('media_rating_id')
                ->references('id')
                ->on(MediaRating::TABLE_NAME)
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('rating_category_id')
                ->references('id')
                ->on(RatingCategory::TABLE_NAME)
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists(RatingCategoryScore::TABLE_NAME);
    }
};
