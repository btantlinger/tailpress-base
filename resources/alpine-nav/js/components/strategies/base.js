// Alpine Anchor now handles all viewport positioning
// No custom positioning utilities needed

export const baseNavItemHandler = {
    // Universal state that most interactive strategies need
    desktopOpen: false,
    ready: false,

    // Timing properties (can be overridden per strategy)
    openDelay: 150,
    closeDelay: 250,
    openTimeout: null,
    closeTimeout: null,

    // Width matching properties
    anchorElementId: null,
    matchWidthOfAnchor: false,
    resizeObserver: null,

    // Core delay methods - pure timing logic, no strategy-specific behavior
    scheduleOpen() {
        this.clearTimeouts();

        if (this.desktopOpen) return;

        this.openTimeout = setTimeout(() => {
            this.executeOpen();
            this.openTimeout = null;
        }, this.openDelay);
    },

    scheduleClose() {
        this.clearTimeouts();

        this.closeTimeout = setTimeout(() => {
            this.executeClose();
            this.closeTimeout = null;
        }, this.closeDelay);
    },

    clearTimeouts() {
        if (this.openTimeout) {
            clearTimeout(this.openTimeout);
            this.openTimeout = null;
        }
        if (this.closeTimeout) {
            clearTimeout(this.closeTimeout);
            this.closeTimeout = null;
        }
    },

    // Default implementations - strategies SHOULD override these
    executeOpen() {
        this.desktopOpen = true;
    },

    executeClose() {
        this.desktopOpen = false;
    },

    // Width matching implementation
    applyWidthMatching() {
        // Only apply if matchWidthOfAnchor is enabled and anchorElementId is set
        if (!this.matchWidthOfAnchor || !this.anchorElementId) {
            return;
        }

        const submenu = this.$el.querySelector('.wm-nav__submenu');
        const anchorElement = document.querySelector(this.anchorElementId);

        if (!submenu || !anchorElement) {
            return;
        }

        // Function to update submenu width
        const updateWidth = () => {
            const anchorWidth = anchorElement.offsetWidth;
            submenu.style.width = `${anchorWidth}px`;
        };

        // Apply width immediately
        updateWidth();

        // Watch for resize changes using ResizeObserver
        if ('ResizeObserver' in window) {
            this.resizeObserver = new ResizeObserver(() => {
                updateWidth();
            });
            this.resizeObserver.observe(anchorElement);
        } else {
            // Fallback to window resize for older browsers
            const handleResize = () => updateWidth();
            window.addEventListener('resize', handleResize);

            // Store cleanup function
            this.$el._cleanupResize = () => {
                window.removeEventListener('resize', handleResize);
            };
        }
    },

    // Cleanup method for width matching
    cleanupWidthMatching() {
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
            this.resizeObserver = null;
        }

        if (this.$el._cleanupResize) {
            this.$el._cleanupResize();
            delete this.$el._cleanupResize;
        }
    },

    // Public interface that strategies can use as-is or override
    openSubmenu(e) {
        this.scheduleOpen();
    },

    closeSubmenu(e) {
        // Handle click.away events (click event with target)
        if (e && e.type === 'click' && e.target) {
            const target = e.target;

            // Check if click was inside an autocomplete panel or item
            // Algolia panels may be detached to body, so check globally
            const isAutocompleteElement = target.closest('.aa-Panel, .aa-Autocomplete, .aa-Item, .aa-List, [class*="autocomplete"]');
            if (isAutocompleteElement) {
                // If this menu contains an autocomplete, assume the panel belongs to it
                const submenu = this.$el.querySelector('.wm-nav__submenu');
                if (submenu) {
                    const autocompleteInMenu = submenu.querySelector('.aa-Autocomplete, .location-autocomplete, [x-data*="locationAutocomplete"]');
                    if (autocompleteInMenu) {
                        return; // Don't close - click was likely on our autocomplete's panel
                    }
                }
            }
        }

        // Handle mouseleave events (mouse event with relatedTarget)
        if (e && e.relatedTarget) {
            const relatedTarget = e.relatedTarget;

            // If moving to an element inside this menu item, don't close
            if (this.$el.contains(relatedTarget)) {
                return;
            }

            // Check if moving to an autocomplete panel (may be positioned outside but logically inside)
            const isAutocompletePanel = relatedTarget.closest('.aa-Panel, .aa-Autocomplete, .aa-Item, .aa-List, [class*="autocomplete"]');
            if (isAutocompletePanel) {
                // Verify the autocomplete is associated with this menu
                const submenu = this.$el.querySelector('.wm-nav__submenu');
                if (submenu) {
                    const autocompleteInMenu = submenu.querySelector('.aa-Autocomplete, .location-autocomplete, [x-data*="locationAutocomplete"]');
                    if (autocompleteInMenu) {
                        return; // Don't close - mouse is on our autocomplete's panel
                    }
                }
            }
        }

        this.scheduleClose();
    },

    // Universal utility
    hasChildren() {
        return this.$el.querySelector('.wm-nav__list') !== null;
    },

    // Standard strategy interface - default implementations
    init() {
        // Watch for menu opening to apply width matching
        if (this.matchWidthOfAnchor) {
            this.$watch('desktopOpen', (isOpen) => {
                if (isOpen) {
                    // Wait for submenu to be visible before applying width
                    this.$nextTick(() => {
                        this.applyWidthMatching();
                    });
                }
            });
        }

        // Cleanup on element removal
        this.$el.addEventListener('alpine:destroying', () => {
            this.cleanupWidthMatching();
        });
    },

    classes() {
        return {};
    },

    styles() {
        return {};
    },

    submenuClasses() {
        return {
            'wm-nav__submenu--open': this.desktopOpen,
            'wm-nav__submenu--ready': this.ready,
        };
    },

    submenuStyles() {
        return {};
    }
};
