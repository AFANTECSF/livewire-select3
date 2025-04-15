<?php

namespace afantecsf\LivewireSelect3\Components;

use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class Select3 extends Component
{
    /**
     * Component configuration
     */
    public string $name = '';

    public string $id = '';
    public string $placeholder = 'Select option';
    public string $dependsOn = '';
    public ?string $model = null;
    public ?string $apiEndpoint = null;
    public array $staticOptions = [];
    public ?string $filterCallback = null;
    public array $additionalParams = [];

    #[Modelable]
    public ?string $selectedValue = null;

    public string $displayField = 'name';
    public string $valueField = 'id';
    public int $minInputLength = 2;
    public int $debounce = 300;
    public int $maxResults = 10;

    /**
     * Internal state
     */
    public string $search = '';

    public array $options = [];
    public bool $isLoading = false;
    public bool $isDisabled = false;
    protected mixed $parentValue = null;
    public string $dependentPlaceholder = '';

    public function mount(
        string $name,
        string $id = '',
        string $placeholder = 'Select option',
        string $dependsOn = '',
        ?string $model = null,
        ?string $apiEndpoint = null,
        array $staticOptions = [],
        ?string $filterCallback = null,
        array $additionalParams = [],
        ?string $selectedValue = null,
        ?string $displayField = null,
        ?string $valueField = null,
        int $minInputLength = 2,
        int $debounce = 300,
        int $maxResults = 10,
        string $dependentPlaceholder = ''
    ) {
        $this->name = $name;
        $this->id = $id ?: $name;
        $this->placeholder = $placeholder;
        $this->dependsOn = $dependsOn;
        $this->model = $model;
        $this->apiEndpoint = $apiEndpoint;
        $this->staticOptions = $staticOptions;
        $this->filterCallback = $filterCallback;
        $this->selectedValue = $selectedValue;
        $this->displayField = $displayField ?? 'name';
        $this->valueField = $valueField ?? 'id';
        $this->minInputLength = $minInputLength;
        $this->debounce = $debounce;
        $this->maxResults = $maxResults;
        $this->additionalParams = $additionalParams;
        $this->dependentPlaceholder = $dependentPlaceholder ?: __('Select parent option');

        if ($this->dependsOn) {
            $this->isDisabled = true;
        }

        $this->loadInitialOptions();
    }

    protected function loadInitialOptions(): void
    {
        if (! empty($this->staticOptions)) {
            $this->normalizeOptions();
        } elseif (! $this->dependsOn && ($this->model || $this->apiEndpoint)) {
            $this->loadOptions(true);
        }
    }

    public function initializeComponent(): void
    {
        if ($this->selectedValue) {
            $this->dispatch('select3:updated', $this->id, $this->selectedValue);
        }
    }

    #[On('select3:updated')]
    public function handleDependentUpdate(string $id, mixed $value, ?string $name = null): void
    {
        // Handle internal dependencies
        if ($this->dependsOn === $id) {
            $previousValue = $this->selectedValue; // Store the previous selection
            $this->parentValue = $value;
            $this->isDisabled = empty($value);
            $this->search = '';
            $this->options = [];

            if (! empty($value)) {
                // Load options first
                $this->loadOptions(true);

                // Only clear selection if the previous value doesn't exist in new options
                if ($previousValue && ! empty($this->options)) {
                    $valueExists = collect($this->options)->contains('value', $previousValue);
                    if (! $valueExists) {
                        $this->selectedValue = null;
                    } else {
                        $this->selectedValue = $previousValue;
                    }
                } else {
                    $this->selectedValue = null;
                }
            } else {
                $this->selectedValue = null;
            }

            $this->dispatch('select3:child-updated', $this->id);
        }
    }

    public function updatedSearch(): void
    {
        if (empty($this->search)) {
            $this->loadInitialOptions();

            return;
        }

        if ($this->minInputLength > 0 && strlen($this->search) < $this->minInputLength) {
            return;
        }

        $this->loadOptions();
    }

    public function updatedSelectedValue(): void
    {
        $this->dispatch(
            'select3:updated',
            id: $this->id,
            value: $this->selectedValue,
            name: $this->name
        );
    }

    protected function loadOptions($loadingSelected = false): void
    {
        if ($this->isDisabled) {
            return;
        }

        if ($this->dependsOn && $this->parentValue === null) {
            $this->options = [];

            return;
        }

        $this->isLoading = true;

        try {
            if ($this->model) {
                $this->loadFromModel($loadingSelected);
            } elseif ($this->apiEndpoint) {
                $this->loadFromApi($loadingSelected);
            } elseif ($this->staticOptions) {
                $this->normalizeOptions();
            }
        } finally {
            $this->isLoading = false;
        }
    }

    protected function loadFromModel($loadingSelected = false): void
    {
        if (! class_exists($this->model)) {
            return;
        }

        $query = app($this->model)->query();

        if ($this->dependsOn && $this->parentValue !== null) {
            $query->where($this->dependsOn, $this->parentValue);
        }

        if ($this->filterCallback && method_exists($this->model, $this->filterCallback)) {
            $query = app($this->model)::{$this->filterCallback}($query, $this->additionalParams);
        }

        // Modified this section to include selected value in search
        // Modified this section to include selected value in search
        if (! empty($this->search)) {
            if (method_exists($this->model, 'scopeSearch')) {
                $query->search($this->search);
            } else {
                // $query->where(function ($q) {
                //     $q->where($this->displayField, 'like', "%{$this->search}%");
                //     if ($this->selectedValue) {
                //         $q->orWhere($this->valueField, $this->selectedValue);
                //     }
                // });

                $query->where(function ($q) {
                    // 1. First try searching database columns
                    if ($this->model && is_object(app($this->model)) && Schema::hasColumn(app($this->model)->getTable(), $this->displayField)) {
                        $q->where($this->displayField, 'like', "%{$this->search}%");
                    }

                    // 2. Check if it's an accessor
                    $accessor = 'get' . Str::studly($this->displayField) . 'Attribute';
                    if (method_exists($this->model, $accessor)) {
                        // Get all records and filter locally (warning: performance impact)
                        $matchingIds = $this->model::all()
                            ->filter(function ($item) {
                                return str_contains(
                                    strtolower($item->{$this->displayField}), 
                                    strtolower($this->search)
                                );
                            })
                            ->pluck('id');

                        if ($matchingIds->isNotEmpty()) {
                            $q->orWhereIn('id', $matchingIds);
                        }
                    }
                    if ($this->selectedValue) {
                                 $q->orWhere($this->valueField, $this->selectedValue);
                             }
                });
            }

            foreach ($this->additionalParams as $field => $value) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
}

        foreach ($this->additionalParams as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        $this->options = $query
            ->when(! $loadingSelected && $this->maxResults > 0, fn ($q) => $q->limit($this->maxResults))
            ->get()
            ->map(fn ($item) => $this->formatOption($item->{$this->valueField}, $item->{$this->displayField}))
            ->toArray();
    }
    protected function loadFromApi($loadingSelected = false): void
    {
        if (! $this->apiEndpoint) {
            return;
        }

        $params = $loadingSelected
            ? ['id' => $this->selectedValue]
            : array_merge(
                [
                    'search' => $this->search,
                    'limit' => $this->maxResults > 0 ? $this->maxResults : null,
                ],
                $this->additionalParams
            );

        if ($this->dependsOn && $this->parentValue !== null) {
            $params['parent_value'] = $this->parentValue;
        }

        try {
            $response = Http::get($this->apiEndpoint, $params);
            if ($response->successful()) {
                $data = $response->json();
                $this->options = collect($data)
                    ->map(fn ($item) => $this->formatOption(
                        $this->getOptionValue($item),
                        $this->getOptionText($item)
                    ))
                    ->filter(fn ($option) => ! is_null($option['value']) && ! is_null($option['text']))
                    ->when($this->maxResults > 0 && ! $loadingSelected, fn ($collection) => $collection->take($this->maxResults))
                    ->values()
                    ->toArray();
            }
        } catch (\Exception $e) {
            $this->options = [];
        }
    }

    protected function normalizeOptions(): void
    {
        if (empty($this->staticOptions)) {
            return;
        }

        $this->options = collect($this->staticOptions)
            ->when($this->search, fn ($options) => $options->filter(
                fn ($option) => str_contains(
                    strtolower((string) ($this->getOptionText($option))),
                    strtolower($this->search)
                )
            ))
            ->map(fn ($option) => $this->formatOption(
                $this->getOptionValue($option),
                $this->getOptionText($option)
            ))
            ->filter(fn ($option) => ! is_null($option['value']) && ! is_null($option['text']))
            ->values()
            ->when($this->maxResults > 0, fn ($collection) => $collection->take($this->maxResults))
            ->toArray();
    }

    protected function getOptionValue($option): mixed
    {
        return $option[$this->valueField] ?? $option['value'] ?? null;
    }

    protected function getOptionText($option): mixed
    {
        return $option[$this->displayField] ?? $option['text'] ?? null;
    }

    protected function formatOption($value, $text): array
    {
        return [
            'value' => $value,
            'text' => $text,
        ];
    }

    public function render()
    {
        return view('livewire-select3::select3');
    }
}
