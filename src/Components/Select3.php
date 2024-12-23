<?php

namespace afantecsf\LivewireSelect3\Components;

use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;
use Livewire\Component;

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
    public int $maxResults = 10; // 0 means no limit

    /**
     * Internal state
     */
    public string $search = '';

    public array $options = [];
    public bool $isLoading = false;
    public bool $isDisabled = false;

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
        int $maxResults = 10
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

        // Load initial options
        $this->loadInitialOptions();
    }

    protected function loadInitialOptions(): void
    {
        if (! empty($this->staticOptions)) {
            $this->normalizeOptions();
        } elseif ($this->model || $this->apiEndpoint) {
            $this->loadOptions(true);
        }
    }

    #[On('select3:updated')]
    public function handleDependentUpdate($parentId, $value): void
    {
        if ($this->dependsOn === $parentId) {
            session()->put("select3_parent_{$parentId}", $value);
            $this->isDisabled = false;
            $this->search = '';
            $this->options = [];
            $this->selectedValue = null;
            $this->loadInitialOptions();
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
        $this->dispatch('select3:updated', $this->id, $this->selectedValue);
        $this->dispatch('select3:value-updated', $this->selectedValue);
    }

    protected function loadOptions($loadingSelected = false): void
    {
        if ($this->isDisabled) {
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

        if ($this->dependsOn && session()->has("select3_parent_{$this->dependsOn}")) {
            $query->where($this->dependsOn, session()->get("select3_parent_{$this->dependsOn}"));
        }

        if ($this->filterCallback && method_exists($this->model, $this->filterCallback)) {
            $query = app($this->model)::{$this->filterCallback}($query, $this->additionalParams);
        }

        // Only apply search if we're not loading the selected value and there's a search term
        if (! $loadingSelected && ! empty($this->search)) {
            if (method_exists($this->model, 'scopeSearch')) {
                $query->search($this->search);
            } else {
                $query->where($this->displayField, 'like', "%{$this->search}%");
            }
        }

        // If loading selected value, filter by it
        if ($loadingSelected && $this->selectedValue) {
            $query->where($this->valueField, $this->selectedValue);
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

        if ($this->dependsOn && session()->has("select3_parent_{$this->dependsOn}")) {
            $params['parent_value'] = session()->get("select3_parent_{$this->dependsOn}");
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

    public function render()
    {
        return view('livewire-select3::select3');
    }
}
