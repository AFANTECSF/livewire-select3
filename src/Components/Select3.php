<?php

namespace afantecsf\LivewireSelect3\Components;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Modelable;

class Select3 extends Component
{
    /**
     * Component configuration
     */
    public string $name = '';                     // Input name
    public string $id = '';                       // Component ID
    public string $placeholder = 'Select option'; // Placeholder text
    public string $dependsOn = '';               // ID of parent select (if dependent)
    public ?string $model = null;                // Model class for dynamic options
    public ?string $apiEndpoint = null;          // API endpoint for remote data
    public array $staticOptions = [];            // Static options array
    public ?string $filterCallback = null;       // Custom filter callback
    public array $additionalParams = [];         // Additional parameters for filtering
    #[Modelable]
    public ?string $selectedValue = null;        // Currently selected value
    public string $displayField = 'name';        // Field to display in options
    public string $valueField = 'id';            // Field to use as value
    public int $minInputLength = 2;              // Min chars before search
    public int $debounce = 300;                  // Debounce delay in ms

    /**
     * Internal state
     */
    public string $search = '';                  // Search query
    public array $options = [];                  // Current options list
    public bool $isLoading = false;              // Loading state
    public bool $isDisabled = false;             // Disabled state

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
        string $displayField = 'name',
        string $valueField = 'id',
        int $minInputLength = 2,
        int $debounce = 300
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
        $this->displayField = $displayField;
        $this->valueField = $valueField;
        $this->minInputLength = $minInputLength;
        $this->debounce = $debounce;
        $this->additionalParams = $additionalParams;

        // If we have static options, load them immediately
        if (!empty($staticOptions)) {
            $this->options = $staticOptions;
        }

        // If dependent and no parent value, disable
        if ($dependsOn && !session()->has("select3_parent_{$dependsOn}")) {
            $this->isDisabled = true;
        }
    }

    #[On('select3:updated')]
    public function handleDependentUpdate($parentId, $value)
    {
        if ($this->dependsOn === $parentId) {
            session()->put("select3_parent_{$parentId}", $value);
            $this->isDisabled = false;
            $this->search = '';
            $this->options = [];
            $this->selectedValue = null;
        }
    }

    public function updatedSearch()
    {
        if (strlen($this->search) < $this->minInputLength) {
            $this->options = [];
            return;
        }

        $this->loadOptions();
    }

    public function updatedSelectedValue()
    {
        $this->dispatch('select3:updated', $this->id, $this->selectedValue);
        $this->dispatch('select3:value-updated', $this->selectedValue);
    }

    public function getListeners()
    {
        return array_merge(parent::getListeners(), [
            'select3:value-updated' => 'handleValueUpdate'
        ]);
    }

    public function handleValueUpdate($value)
    {
        $this->selectedValue = $value;
    }

    protected function loadOptions()
    {
        if ($this->isDisabled) {
            return;
        }

        $this->isLoading = true;

        try {
            if ($this->model) {
                $this->loadFromModel();
            } elseif ($this->apiEndpoint) {
                $this->loadFromApi();
            } elseif ($this->staticOptions) {
                $this->filterStaticOptions();
            }
        } finally {
            $this->isLoading = false;
        }
    }

    protected function loadFromModel()
    {
        if (!class_exists($this->model)) {
            return;
        }

        $query = app($this->model)->query();

        // Add dependent condition if applicable
        if ($this->dependsOn && session()->has("select3_parent_{$this->dependsOn}")) {
            $parentValue = session()->get("select3_parent_{$this->dependsOn}");
            $query->where($this->dependsOn, $parentValue);
        }

        // Apply custom filter callback if provided
        if ($this->filterCallback && method_exists($this->model, $this->filterCallback)) {
            $query = app($this->model)::{$this->filterCallback}($query, $this->additionalParams);
        }

        // Search condition
        if (method_exists($this->model, 'scopeSearch')) {
            // Use custom search scope if defined
            $query->search($this->search);
        } else {
            // Default search behavior
            $query->where($this->displayField, 'like', "%{$this->search}%");
        }

        // Apply any additional where conditions from params
        foreach ($this->additionalParams as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        $this->options = $query->limit(10)->get()
            ->map(fn($item) => [
                'value' => $item->{$this->valueField},
                'text' => $item->{$this->displayField}
            ])
            ->toArray();
    }

    protected function loadFromApi()
    {
        if (!$this->apiEndpoint) {
            return;
        }

        $params = array_merge([
            'search' => $this->search,
            'limit' => 10
        ], $this->additionalParams);

        if ($this->dependsOn && session()->has("select3_parent_{$this->dependsOn}")) {
            $params['parent_value'] = session()->get("select3_parent_{$this->dependsOn}");
        }

        try {
            $response = Http::get($this->apiEndpoint, $params);
            if ($response->successful()) {
                $data = $response->json();
                $this->options = collect($data)
                    ->map(fn($item) => [
                        'value' => $item[$this->valueField],
                        'text' => $item[$this->displayField]
                    ])
                    ->toArray();
            }
        } catch (\Exception $e) {
            $this->options = [];
        }
    }

    protected function filterStaticOptions()
    {
        if (empty($this->staticOptions)) {
            return;
        }

        $this->options = collect($this->staticOptions)
            ->filter(fn($option) =>
            str_contains(
                strtolower($option[$this->displayField]),
                strtolower($this->search)
            )
            )
            ->take(10)
            ->toArray();
    }

    public function render()
    {
        return view('livewire-select3::select3');
    }
}