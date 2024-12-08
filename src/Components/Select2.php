<?php

namespace JekHar\LivewireSelect2\Components;

use Livewire\Component;
use Livewire\Attributes\On;
use JekHar\LivewireSelect2\Facades\LivewireSelect2;
use Illuminate\Support\Facades\Http;

class Select2 extends Component
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
    public ?string $selectedValue = null;        // Currently selected value
    public string $displayField = 'name';        // Field to display in options
    public string $valueField = 'id';            // Field to use as value
    public int $minInputLength = 2;              // Min chars before search
    public int $debounce = 300;                  // Debounce delay in ms
    public array $additionalParams = [];         // Additional parameters for filtering

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
        ?string $selectedValue = null,
        string $displayField = 'name',
        string $valueField = 'id',
        int $minInputLength = 2,
        int $debounce = 300,
        array $additionalParams = []
    ) {
        // Apply global config if available
        $globalConfig = LivewireSelect2::getConfig();
        foreach ($globalConfig as $key => $value) {
            if (!isset($this->$key)) {
                $this->$key = $value;
            }
        }

        $this->name = $name;
        $this->id = $id ?: $name;
        $this->placeholder = $placeholder;
        $this->dependsOn = $dependsOn;
        $this->model = $model;
        $this->apiEndpoint = $apiEndpoint;
        $this->staticOptions = $staticOptions;
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
        if ($dependsOn && !session()->has("select2_parent_{$dependsOn}")) {
            $this->isDisabled = true;
        }
    }

    #[On('select2:updated')]
    public function handleDependentUpdate($parentId, $value)
    {
        if ($this->dependsOn === $parentId) {
            session()->put("select2_parent_{$parentId}", $value);
            $this->isDisabled = false;
            $this->resetState();
        }
    }

    protected function resetState()
    {
        $this->search = '';
        $this->options = [];
        $this->selectedValue = null;
        $this->isLoading = false;
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
        $this->dispatch('select2:updated', $this->id, $this->selectedValue);
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
        } catch (\Exception $e) {
            $this->options = [];
            // You might want to log the error here
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

        // Apply registered filters if specified
        if (!empty($this->additionalParams['filters'])) {
            foreach ($this->additionalParams['filters'] as $filterName) {
                if ($filter = LivewireSelect2::getFilter($filterName)) {
                    $query = $filter($query);
                }
            }
        }

        // Add dependent condition if applicable
        if ($this->dependsOn && session()->has("select2_parent_{$this->dependsOn}")) {
            $parentValue = session()->get("select2_parent_{$this->dependsOn}");
            $query->where($this->dependsOn, $parentValue);
        }

        // Apply any additional where conditions from params
        foreach ($this->additionalParams as $field => $value) {
            if ($field !== 'filters' && $value !== null) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
        }

        // Search condition
        if (method_exists($this->model, 'scopeSearch')) {
            $query->search($this->search);
        } else {
            $query->where($this->displayField, 'like', "%{$this->search}%");
        }

        $this->options = $query->limit(10)
            ->get()
            ->map(fn($item) => [
                'value' => data_get($item, $this->valueField),
                'text' => data_get($item, $this->displayField)
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

        if ($this->dependsOn && session()->has("select2_parent_{$this->dependsOn}")) {
            $params['parent_value'] = session()->get("select2_parent_{$this->dependsOn}");
        }

        try {
            $response = Http::get($this->apiEndpoint, $params);
            if ($response->successful()) {
                $data = $response->json();
                $this->options = collect($data)->map(fn($item) => [
                    'value' => data_get($item, $this->valueField),
                    'text' => data_get($item, $this->displayField)
                ])->toArray();
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
                strtolower(data_get($option, $this->displayField)),
                strtolower($this->search)
            )
            )
            ->take(10)
            ->toArray();
    }

    public function render()
    {
        return view('livewire-select2::select2');
    }
}