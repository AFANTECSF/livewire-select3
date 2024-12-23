// resources/assets/js/select3.js
window.select3Alpine = function() {
    return {
        open: false,
        selectedText: '',
        highlightedIndex: -1,

        init() {
            this.initializeSelectedText();
            this.setupWatchers();
            this.setupKeyboardEvents();
        },

        initializeSelectedText() {
            if (this.$wire.selectedValue) {
                const option = this.$wire.options.find(opt => opt.value == this.$wire.selectedValue);
                if (option) {
                    this.selectedText = option.text;
                }
            }
        },

        setupWatchers() {
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

            this.$watch('$wire.options', options => {
                if (this.$wire.selectedValue) {
                    const option = options.find(opt => opt.value == this.$wire.selectedValue);
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
                        this.$refs.searchInput.focus();
                    });
                }
            });
        },

        handleKeydown(event) {
            if (!this.open) return;

            const options = this.$refs.optionsList.querySelectorAll('.list-group-item');

            switch(event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.highlightedIndex = Math.min(this.highlightedIndex + 1, options.length - 1);
                    if (this.highlightedIndex === -1 && options.length > 0) {
                        this.highlightedIndex = 0;
                    }
                    this.scrollToHighlighted();
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.highlightedIndex = Math.max(this.highlightedIndex - 1, 0);
                    this.scrollToHighlighted();
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (this.highlightedIndex >= 0 && options[this.highlightedIndex]) {
                        options[this.highlightedIndex].click();
                    } else if (options.length > 0) {
                        options[0].click();
                    }
                    break;
                case 'Escape':
                    this.open = false;
                    break;
            }
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                const options = this.$refs.optionsList.querySelectorAll('.list-group-item');
                const highlighted = options[this.highlightedIndex];
                const dropdownContainer = highlighted?.closest('.select3-dropdown');

                if (highlighted && dropdownContainer) {
                    const containerRect = dropdownContainer.getBoundingClientRect();
                    const highlightedRect = highlighted.getBoundingClientRect();

                    if (highlightedRect.bottom > containerRect.bottom) {
                        highlighted.scrollIntoView({ block: 'end', behavior: 'smooth' });
                    } else if (highlightedRect.top < containerRect.top) {
                        highlighted.scrollIntoView({ block: 'start', behavior: 'smooth' });
                    }
                }
            });
        },

        isHighlighted(index) {
            return this.highlightedIndex === index;
        }
    };
};