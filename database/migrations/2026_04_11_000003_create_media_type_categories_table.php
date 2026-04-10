<?php

use App\Models\MediaTypeCategory;
use App\Models\RatingCategory;
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
        Schema::create(MediaTypeCategory::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->string('media_type');
            $table->unsignedBigInteger('rating_category_id');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::table(MediaTypeCategory::TABLE_NAME, function (Blueprint $table) {
            // Set index key constraints
            $table->index('media_type');

            // Set unique key constraints
            $table->unique(['media_type', 'rating_category_id']);

            // Set foreign key constraints
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
        Schema::dropIfExists(MediaTypeCategory::TABLE_NAME);
    }
};
