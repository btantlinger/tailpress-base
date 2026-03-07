import { baseNavItemHandler } from './base.js';

export default {

    navItemHandler(userConfig = {}) {
        const defaults = {
            level: 0,
            openDelay: 150, // delay in milliseconds before opening
            closeDelay: 250, // delay in milliseconds before closing
            anchorElementId: null,
            matchWidthOfAnchor: false,
        };

        const cfg = {...defaults, ...userConfig};

        return {
            ...baseNavItemHandler,

            // Override timing configuration
            openDelay: cfg.openDelay,
            closeDelay: cfg.closeDelay,

            // Strategy-specific properties
            level: cfg.level,
            anchorElementId: cfg.anchorElementId,
            matchWidthOfAnchor: cfg.matchWidthOfAnchor,

            init() {
                this.$el.removeAttribute('x-cloak');

                    // Call base init for width matching
                    baseNavItemHandler.init.call(this);

                // Alpine Anchor in HTML handles positioning for all levels

                // Watch for open state to set ready flag
                this.$watch('desktopOpen', open => {
                    if (open) {
                        this.ready = false;
                        this.$nextTick(() => {
                            this.ready = true;
                        });
                    } else {
                        this.ready = false;
                    }
                });
            },

            // Control when submenu should be shown via x-show
            shouldShowSubmenu() {
                // All levels controlled by hover state for drill-down
                return this.desktopOpen;
            },

            classes() {
                const classList = [];

                classList.push('wm-nav__item');
                classList.push('wm-nav__item--level-' + this.level);

                if (this.hasChildren()) classList.push('wm-nav__item--parent');
                if (this.level > 0) classList.push('wm-nav__item--child');
                if (this.desktopOpen) classList.push('wm-nav__item--active');
                if (this.level === 0) classList.push('wm-nav__item--root');

                return classList.join(' ');
            },

            styles() {
                return {};
            },

            submenuStyles() {
                return {};
            },
        };
    },
};


