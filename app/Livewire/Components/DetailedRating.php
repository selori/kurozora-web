<?php

namespace App\Livewire\Components;

use App\Enums\RatingStyle;
use App\Models\MediaRating;
use App\Models\MediaTypeCategory;
use App\Models\RatingCategoryScore;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Isolate;
use Livewire\Component;

#[Isolate]
class DetailedRating extends Component
{
    /**
     * The id the model.
     *
     * @var string|null
     */
    public ?string $modelID;

    /**
     * The type of the model.
     *
     * @var string|null
     */
    public ?string $modelType;

    /**
     * The overall rating (calculated from category scores).
     *
     * @var float|null $rating
     */
    public ?float $rating;

    /**
     * The scores for each category.
     *
     * @var array $categoryScores
     */
    public array $categoryScores = [];

    /**
     * Whether the modal is open.
     *
     * @var bool $showModal
     */
    public bool $showModal = false;

    /**
     * Whether interaction with the rating is disabled.
     *
     * @var bool $disabled
     */
    public bool $disabled;

    /**
     * Determines whether to load the section.
     *
     * @var bool $readyToLoad
     */
    public bool $readyToLoad = false;

    /**
     * The component's listeners.
     *
     * @return array
     */
    protected function getListeners(): array
    {
        return $this->disabled ? [] : [
            $this->listenerKey() => 'handleRatingUpdate',
        ];
    }

    /**
     * The listener key of the component.
     *
     * @return string
     */
    protected function listenerKey(): string
    {
        return 'detailed-rating-updated-' . $this->modelID . '-' . $this->modelType;
    }

    /**
     * Prepare the component.
     *
     * @param null|string $modelId
     * @param null|string $modelType
     * @param null|float  $rating Internal rating value (0-10)
     * @param bool        $disabled
     *
     * @return void
     */
    function mount(?string $modelId = null, ?string $modelType = null, ?float $rating = null, bool $disabled = false): void
    {
        $this->modelID = $modelId;
        $this->modelType = $modelType;
        $this->rating = $rating ?? MediaRating::MIN_RATING_VALUE;
        $this->disabled = $disabled;
    }

    /**
     * Sets the property to load the section.
     *
     * @return void
     */
    public function loadSection(): void
    {
        $this->readyToLoad = true;
        $this->loadExistingScores();
    }

    /**
     * Load existing category scores if available.
     *
     * @return void
     */
    protected function loadExistingScores(): void
    {
        $user = auth()->user();

        if (empty($user)) {
            return;
        }

        $mediaRating = $user->mediaRatings()
            ->withoutGlobalScopes()
            ->where([
                ['model_id', '=', $this->modelID],
                ['model_type', '=', $this->modelType],
            ])
            ->first();

        if ($mediaRating) {
            $existingScores = $mediaRating->categoryScores()->with('ratingCategory')->get();
            foreach ($existingScores as $score) {
                $this->categoryScores[$score->rating_category_id] = $score->score;
            }
        }

        // Initialize any missing categories with 0
        $categories = $this->categories;
        foreach ($categories as $category) {
            if (!isset($this->categoryScores[$category->rating_category_id])) {
                $this->categoryScores[$category->rating_category_id] = 5.0;
            }
        }
    }

    /**
     * Open the detailed rating modal.
     *
     * @return void
     */
    public function openModal(): void
    {
        $this->showModal = true;
        if (!$this->readyToLoad) {
            $this->loadSection();
        }
    }

    /**
     * Close the modal.
     *
     * @return void
     */
    public function closeModal(): void
    {
        $this->showModal = false;
    }

    /**
     * Get the rating categories for this media type.
     *
     * @return Collection
     */
    public function getCategoriesProperty(): Collection
    {
        if (!$this->readyToLoad) {
            return collect();
        }

        return MediaTypeCategory::getCategoriesForMediaType($this->modelType);
    }

    /**
     * Calculate the overall rating from category scores.
     *
     * @return float
     */
    protected function calculateOverall(): float
    {
        $categories = $this->categories;

        if ($categories->isEmpty()) {
            return 5.0;
        }

        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($categories as $category) {
            $score = $this->categoryScores[$category->rating_category_id] ?? 5.0;
            $weight = $category->ratingCategory->weight ?? 1.0;
            $weightedSum += $score * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight === 0) {
            return 5.0;
        }

        return round($weightedSum / $totalWeight, 1);
    }

    /**
     * Updates the authenticated user's rating of the model.
     *
     * @return RedirectResponse|void
     */
    public function rate()
    {
        $user = auth()->user();

        if (empty($user)) {
            return to_route('sign-in');
        }

        $this->rating = $this->calculateOverall();

        // Update or create the main rating
        $mediaRating = $user->mediaRatings()->withoutGlobalScopes()
            ->updateOrCreate([
                'model_id' => $this->modelID,
                'model_type' => $this->modelType,
            ], [
                'rating' => $this->rating,
                'rating_style' => RatingStyle::Detailed,
            ]);

        // Save category scores
        foreach ($this->categoryScores as $categoryId => $score) {
            RatingCategoryScore::updateOrCreate([
                'media_rating_id' => $mediaRating->id,
                'rating_category_id' => $categoryId,
            ], [
                'score' => $score,
            ]);
        }

        $this->closeModal();

        $this->dispatch($this->listenerKey(), id: $this->getID(), modelID: $this->modelID, modelType: $this->modelType, rating: $this->rating);
        // Also dispatch the standard star-rating event for other components
        $this->dispatch('star-rating-updated-' . $this->modelID . '-' . $this->modelType, id: $this->getID(), modelID: $this->modelID, modelType: $this->modelType, rating: $this->rating);
    }

    /**
     * Remove the rating.
     *
     * @return RedirectResponse|void
     */
    public function removeRating()
    {
        $user = auth()->user();

        if (empty($user)) {
            return to_route('sign-in');
        }

        $user->mediaRatings()->where([
            ['model_id', '=', $this->modelID],
            ['model_type', '=', $this->modelType],
        ])->forceDelete();

        $this->rating = 0;
        $this->categoryScores = [];

        // Reinitialize categories with default values
        $categories = $this->categories;
        foreach ($categories as $category) {
            $this->categoryScores[$category->rating_category_id] = 5.0;
        }

        $this->closeModal();

        $this->dispatch($this->listenerKey(), id: $this->getID(), modelID: $this->modelID, modelType: $this->modelType, rating: null);
        $this->dispatch('star-rating-updated-' . $this->modelID . '-' . $this->modelType, id: $this->getID(), modelID: $this->modelID, modelType: $this->modelType, rating: null);
    }

    /**
     * Handles the event emitted when updating the rating.
     *
     * @param $id
     * @param $modelID
     * @param $modelType
     * @param $rating
     *
     * @return void
     */
    public function handleRatingUpdate($id, $modelID, $modelType, $rating): void
    {
        if (
            $this->getID() != $id &&
            $modelID == $this->modelID &&
            $modelType == $this->modelType
        ) {
            $this->rating = $rating;
        }
    }

    /**
     * Render the component.
     *
     * @return Application|Factory|View
     */
    public function render(): Application|Factory|View
    {
        return view('livewire.components.detailed-rating');
    }
}
