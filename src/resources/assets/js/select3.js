// resources/assets/js/select3.js
window.select3Alpine = function() {
    return {
        open: false,
        selectedText: '',
        highlightedIndex: -1,
        formHiddenInput: null,

        init() {
            // Initialize the form hidden input reference
            this.formHiddenInput = this.$el.querySelector(`input[name="${this.$wire.name}"]`);

            this.initializeSelectedText();
            this.setupWatchers();
            this.setupKeyboardEvents();
            this.setupFormSync();

            // Watch for disabled state
            this.$watch('$wire.isDisabled', value => {
                if (value) {
                    this.open = false;
                }
            });
        },

        setupFormSync() {
            // Ensure form synchronization
            this.$watch('$wire.selectedValue', value => {
                if (this.formHiddenInput) {
                    this.formHiddenInput.value = value || '';

                    // Dispatch a change event for form validation
                    this.formHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });

            // Handle form reset
            const form = this.$el.closest('form');
            if (form) {
                form.addEventListener('reset', () => {
                    setTimeout(() => {
                        this.selectedText = '';
                        if (this.$wire.selectedValue) {
                            this.$wire.selectedValue = null;
                        }
                    }, 0);
                });
            }
        },

        initializeSelectedText() {
            if (this.$wire.selectedValue) {
                const option = this.$wire.options.find(opt => String(opt.value) === String(this.$wire.selectedValue));
                if (option) {
                    this.selectedText = option.text;

                    // Ensure the hidden input is synchronized
                    if (this.formHiddenInput) {
                        this.formHiddenInput.value = this.$wire.selectedValue;
                    }
                }
            }
        },

        setupWatchers() {
            // Watch for selected value changes
            this.$watch('$wire.selectedValue', value => {
                if (value) {
                    const option = this.$wire.options.find(opt => String(opt.value) === String(value));
                    if (option) {
                        this.selectedText = option.text;
                    }
                } else {
                    this.selectedText = '';
                }
            });

            // Watch for options changes
            this.$watch('$wire.options', options => {
                if (this.$wire.selectedValue) {
                    const option = options.find(opt => String(opt.value) === String(this.$wire.selectedValue));
                    if (option) {
                        this.selectedText = option.text;
                    }
                }
                this.highlightedIndex = -1;
            });
        },

        setupKeyboardEvents() {
            this.$watch('open', value => {
                if (value) {
                    this.$nextTick(() => {
                        const searchInput = this.$refs.searchInput;
                        if (searchInput) {
                            searchInput.focus();
                        }
                    });
                }
            });
        },

        handleKeydown(event) {
            if (!this.open) return;

            const options = this.$refs.optionsList?.querySelectorAll('.list-group-item') || [];
            const optionsCount = options.length;

            switch(event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    if (this.highlightedIndex < optionsCount - 1) {
                        this.highlightedIndex++;
                        this.scrollToHighlighted();
                    }
                    break;

                case 'ArrowUp':
                    event.preventDefault();
                    if (this.highlightedIndex > 0) {
                        this.highlightedIndex--;
                        this.scrollToHighlighted();
                    }
                    break;

                case 'Enter':
                    event.preventDefault();
                    if (this.highlightedIndex >= 0 && this.highlightedIndex < optionsCount) {
                        options[this.highlightedIndex].click();
                    }
                    break;

                case 'Escape':
                    event.preventDefault();
                    this.open = false;
                    break;

                case 'Tab':
                    this.open = false;
                    break;
            }
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                const options = this.$refs.optionsList?.querySelectorAll('.list-group-item') || [];
                const highlighted = options[this.highlightedIndex];
                const container = this.$refs.optionsList?.closest('.select3-dropdown');

                if (highlighted && container) {
                    const containerRect = container.getBoundingClientRect();
                    const highlightedRect = highlighted.getBoundingClientRect();

                    if (highlightedRect.bottom > containerRect.bottom) {
                        highlighted.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    } else if (highlightedRect.top < containerRect.top) {
                        highlighted.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                }
            });
        },

        selectOption(value, text) {
            this.selectedText = text;
            this.$wire.selectedValue = value;
            this.open = false;
            this.highlightedIndex = -1;

            // Update the hidden input
            if (this.formHiddenInput) {
                this.formHiddenInput.value = value;
                this.formHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },

        isHighlighted(index) {
            return this.highlightedIndex === index;
        },

        // Helper method to clear the selection
        clearSelection() {
            this.selectedText = '';
            this.$wire.selectedValue = null;
            if (this.formHiddenInput) {
                this.formHiddenInput.value = '';
                this.formHiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    };
};