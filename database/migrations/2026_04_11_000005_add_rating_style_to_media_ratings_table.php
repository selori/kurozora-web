<?php

use App\Enums\RatingStyle;
use App\Models\MediaRating;
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
        Schema::table(MediaRating::TABLE_NAME, function (Blueprint $table) {
            $table->unsignedTinyInteger('rating_style')
                ->default(RatingStyle::Standard)
                ->after('rating');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table(MediaRating::TABLE_NAME, function (Blueprint $table) {
            $table->dropColumn('rating_style');
        });
    }
};
