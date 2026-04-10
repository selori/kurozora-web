<div class="relative">
    {{-- Trigger button showing current rating --}}
    <button
        type="button"
        wire:click="openModal"
        @class([
            'flex items-center gap-2 px-4 py-2 rounded-lg transition-all duration-200',
            'bg-secondary hover:bg-tertiary cursor-pointer' => !$disabled,
            'bg-secondary cursor-not-allowed opacity-75' => $disabled,
        ])
        {{ $disabled ? 'disabled' : '' }}
    >
        @if ($rating > 0)
            <span class="text-lg font-semibold text-tint">{{ number_format($rating, 1) }}</span>
            <span class="text-sm text-secondary">/10</span>
        @else
            <span class="text-sm text-secondary">{{ __('Rate in Detail') }}</span>
        @endif
        @svg('chevron_right', 'w-4 h-4 fill-current text-secondary')
    </button>

    {{-- Modal --}}
    @if ($showModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            wire:click.self="closeModal"
        >
            <div class="w-full max-w-md mx-4 bg-primary rounded-xl shadow-2xl overflow-hidden">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-secondary">
                    <h3 class="text-lg font-semibold text-primary">{{ __('Detailed Rating') }}</h3>
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="p-1 rounded-full hover:bg-secondary transition-colors"
                    >
                        @svg('xmark', 'w-5 h-5 fill-current text-secondary')
                    </button>
                </div>

                {{-- Content --}}
                <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
                    @if ($this->categories->isNotEmpty())
                        <div class="space-y-4">
                            @foreach ($this->categories as $category)
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-sm font-medium text-primary" for="category-{{ $category->rating_category_id }}">
                                            {{ $category->ratingCategory->name }}
                                        </label>
                                        <span class="text-sm font-semibold text-tint">
                                            {{ number_format($categoryScores[$category->rating_category_id] ?? 5, 1) }}
                                        </span>
                                    </div>
                                    @if ($category->ratingCategory->description)
                                        <p class="text-xs text-secondary">{{ $category->ratingCategory->description }}</p>
                                    @endif
                                    <input
                                        type="range"
                                        id="category-{{ $category->rating_category_id }}"
                                        wire:model.live="categoryScores.{{ $category->rating_category_id }}"
                                        min="0"
                                        max="10"
                                        step="0.5"
                                        class="w-full h-2 bg-secondary rounded-lg appearance-none cursor-pointer accent-tint"
                                    />
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-secondary">{{ __('Loading categories...') }}</p>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-secondary bg-secondary/30">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm text-secondary">{{ __('Overall Score') }}</span>
                        <span class="text-2xl font-bold text-tint">{{ number_format($this->calculateOverall(), 1) }}<span class="text-sm font-normal text-secondary">/10</span></span>
                    </div>
                    <div class="flex gap-3">
                        @if ($rating > 0)
                            <button
                                type="button"
                                wire:click="removeRating"
                                class="flex-1 px-4 py-2 text-sm font-medium text-red-500 bg-red-500/10 rounded-lg hover:bg-red-500/20 transition-colors"
                            >
                                {{ __('Remove') }}
                            </button>
                        @endif
                        <button
                            type="button"
                            wire:click="rate"
                            class="flex-1 px-4 py-2 text-sm font-medium text-white bg-tint rounded-lg hover:opacity-90 transition-opacity"
                        >
                            {{ __('Save Rating') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
