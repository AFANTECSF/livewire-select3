<div
        x-data="{
        open: false,
        selectedText: '',
        init() {
            // Initialize selected text from current options if exists
            if (this.$wire.selectedValue) {
                const option = this.$wire.options.find(opt => opt.value == this.$wire.selectedValue);
                if (option) {
                    this.selectedText = option.text;
                }
            }

            // Watch for changes in selectedValue
            this.$watch('$wire.selectedValue', value => {
                if (value) {
                    const option = this.$wire.options.find(opt => opt.value == value);
                    if (option) {
                        this.selectedText = option.text;
                    }
                } else {
                    this.selectedText = '';
                }
            });

            // Watch for changes in options (useful for dependent selects)
            this.$watch('$wire.options', options => {
                if (this.$wire.selectedValue) {
                    const option = options.find(opt => opt.value == this.$wire.selectedValue);
                    if (option) {
                        this.selectedText = option.text;
                    }
                }
            });
        },
        focusSearch() {
            if (this.open) {
                this.$nextTick(() => {
                    this.$refs.searchInput.focus();
                });
            }
        }
    }"
        class="position-relative"
>
    <!-- Hidden input for form submission -->
    <input
            type="hidden"
            name="{{ $name }}"
            wire:model.live="selectedValue"
    >

    <!-- Main select button -->
    <div
            @click="open = !open; focusSearch()"
            class="form-control d-flex justify-content-between align-items-center cursor-pointer {{ $isDisabled ? 'disabled' : '' }}"
            :class="{ 'border-primary': open }"
    >
        <span x-text="selectedText || '{{ $placeholder }}'" class="text-truncate pe-2"></span>
        <i class="fa fa-fw fa-angle-down" :class="{ 'fa-rotate-180': open }"></i>
    </div>

    <!-- Dropdown menu -->
    <div
            x-show="open"
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            class="position-absolute start-0 mt-1 w-100 rounded shadow-sm border bg-body-light z-1055"
            style="max-height: 300px; overflow-y: auto;"
    >
        <!-- Search input -->
        <div class="p-2 border-bottom">
            <input
                    x-ref="searchInput"
                    type="text"
                    class="form-control form-control-sm"
                    placeholder="Search..."
                    wire:model.live.debounce.{{ $debounce }}ms="search"
                    @click.stop
            >
        </div>

        <!-- Loading state -->
        <div wire:loading class="p-3 text-center">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Options list -->
        <div wire:loading.remove>
            @if (empty($options) && strlen($search) >= $minInputLength)
                <div class="p-3 text-center text-muted">
                    No results found
                </div>
            @elseif (strlen($search) < $minInputLength && !empty($search))
                <div class="p-3 text-center text-muted">
                    Type {{ $minInputLength }} or more characters to search
                </div>
            @else
                <div class="list-group list-group-flush">
                    @foreach ($options as $option)
                        <button
                                type="button"
                                class="list-group-item list-group-item-action {{ $selectedValue == $option['value'] ? 'active' : '' }}"
                                wire:key="option-{{ $option['value'] }}"
                                @click="$wire.selectedValue = '{{ $option['value'] }}'; selectedText = '{{ $option['text'] }}'; open = false"
                        >
                            {{ $option['text'] }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>