<div
        x-data="select3Alpine()"
        class="position-relative"
        @keydown="handleKeydown($event)"
        wire:init="initializeComponent"
>
    <style>
        .select3-dropdown {
            z-index: 1060 !important;
        }
        .select3-dropdown .list-group-item-action {
            transition: all 0.2s ease-in-out;
            padding-left: 1rem;
        }
        .select3-dropdown .list-group-item-action:hover:not(.keyboard-selected) {
            background-color: rgba(var(--bs-primary-rgb), 0.1);
        }
        .select3-dropdown .keyboard-selected {
            background-color: rgba(var(--bs-primary-rgb), 0.15) !important;
        }
        .select3-custom-option {
            border-top: 1px dashed #ccc;
            margin-top: 4px;
            padding-top: 4px;
        }
    </style>

    <!-- Hidden input for form submission -->
    <input
            type="hidden"
            name="{{ $name }}"
            x-model="$wire.selectedValue"
    >

    <!-- Main select button -->
    <div
            @click="!$wire.isDisabled && (open = !open)"
            class="form-control d-flex justify-content-between align-items-center cursor-pointer {{ $isDisabled ? 'disabled' : '' }}"
            :class="{ 'border-primary': open }"
    >
        <span x-text="selectedText || '{{ $isDisabled ? $dependentPlaceholder : $placeholder }}'"
              class="text-truncate pe-2 {{ $isDisabled ? 'text-muted' : '' }}">
        </span>
        <i class="fa fa-fw fa-angle-down" :class="{ 'fa-rotate-180': open }"></i>
    </div>

    <!-- Dropdown menu -->
    <div
            x-show="open"
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            class="position-absolute start-0 mt-1 w-100 rounded shadow-sm border bg-body-light z-1055 select3-dropdown"
            :class="{ 'd-none': !open }"
            style="max-height: 300px; overflow-y: auto;"
    >
        <!-- Search input -->
        <div class="p-2 border-bottom">
            <input
                    x-ref="searchInput"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="{{ __('Search...') }}"
                    wire:model.live.debounce.{{ $debounce }}ms="search"
                    @click.stop
            >
        </div>

        <!-- Loading state -->
        <div wire:loading.delay wire:target="search, loadOptions" class="p-3 text-center">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Options list -->
        <div wire:loading.remove wire:target="search, loadOptions" x-ref="optionsList">
            @if (empty($options))
                <div class="p-3 text-center text-muted">
                    {{ strlen($search) >= $minInputLength ? __('No results found') : __('Type to search...') }}
                </div>
                
                <!-- Custom option when no results and allowCustomOption is enabled -->
                @if($allowCustomOption && strlen($search) >= $minInputLength)
                <div class="select3-custom-option">
                    <button
                        type="button"
                        class="list-group-item list-group-item-action"
                        :class="{ 'keyboard-selected': isHighlighted(0) }"
                        wire:key="create-custom-option"
                        @click="open = false; $wire.createCustomOption()"
                        @mouseenter="highlightedIndex = 0"
                    >
                        <i class="fa fa-plus-circle me-1"></i> {{ $customOptionText }} "{{ $search }}"
                    </button>
                </div>
                @endif
            @else
                <div class="list-group list-group-flush">
                    @foreach ($options as $index => $option)
                        <button
                                type="button"
                                class="list-group-item list-group-item-action"
                                :class="{
                                'active': '{{ $selectedValue }}' == '{{ $option['value'] }}',
                                'keyboard-selected': isHighlighted({{ $index }})
                            }"
                                wire:key="option-{{ $option['value'] }}"
                                @click="selectedText = '{{ $option['text'] }}';
                                    open = false;
                                    highlightedIndex = -1;
                                    $wire.set('selectedValue', '{{ $option['value'] }}');"
                                @mouseenter="highlightedIndex = {{ $index }}"
                        >
                            {{ $option['text'] }}
                        </button>
                    @endforeach
                    
                    <!-- Custom option when results exist and allowCustomOption is enabled -->
                    @if($allowCustomOption && strlen($search) >= $minInputLength)
                    <div class="select3-custom-option">
                        <button
                            type="button"
                            class="list-group-item list-group-item-action"
                            :class="{ 'keyboard-selected': isHighlighted({{ count($options) }}) }"
                            wire:key="create-custom-option"
                            @click="open = false; $wire.createCustomOption()"
                            @mouseenter="highlightedIndex = {{ count($options) }}"
                        >
                            <i class="fa fa-plus-circle me-1"></i> {{ $customOptionText }} "{{ $search }}"
                        </button>
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

</div>