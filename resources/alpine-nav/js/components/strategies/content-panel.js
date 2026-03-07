import { baseNavItemHandler } from './base.js';

export default {
    navItemHandler(userConfig = {}) {
        const defaults = {
            level: 0,
            openDelay: 100,
            closeDelay: 200,
            anchorElementId: null,
            matchWidthOfAnchor: false,
            minTileWidth: '200px',
            contentLayout: 'content-below', // content-only, content-above, content-below, content-left, content-right, mixed
            enableDescriptions: false,
        };

        const cfg = {
            ...defaults,
            ...userConfig,
        };

        return {
            ...baseNavItemHandler,

            // Override timing configuration
            openDelay: cfg.openDelay,
            closeDelay: cfg.closeDelay,

            // Configuration (from PHP)
            level: cfg.level,
            anchorElementId: cfg.anchorElementId,
            matchWidthOfAnchor: cfg.matchWidthOfAnchor,
            minTileWidth: cfg.minTileWidth,
            contentLayout: cfg.contentLayout,
            enableDescriptions: cfg.enableDescriptions,

            // Override executeOpen
            executeOpen() {
                this.desktopOpen = true;
                this.$nextTick(() => {
                    this.ready = true;
                });
            },

            init() {
                // Alpine Anchor in HTML handles positioning for level 0
                // Nested items (level 1+) are always visible per CSS

                // Call base init for width matching
                baseNavItemHandler.init.call(this);
            },

            // Control when submenu should be shown via x-show
            shouldShowSubmenu() {
                // Level 0: controlled by hover state
                if (this.level === 0) {
                    return this.desktopOpen;
                }
                // Level 1+: always visible (inline display per CSS)
                return true;
            },

            // --- Computed Properties for Alpine's :class and :style ---
            classes() {
                const classList = [];

                if (this.desktopOpen) {
                    classList.push('wm-nav__item--active');
                }

                // Only add layout and description classes at level 0
                if (this.level === 0) {
                    classList.push(`wm-nav__item--content-layout-${this.contentLayout}`);
                    if (this.enableDescriptions) {
                        classList.push('wm-nav__item--descriptions-enabled');
                    }
                }

                return classList.join(' ');
            },

            submenuStyles() {
                // Only apply custom property for level-0 (main panel)
                if (this.level === 0) {
                    // Ensure minTileWidth has units - add 'px' if it's just a number
                    let tileWidth = this.minTileWidth;
                    if (typeof tileWidth === 'number') {
                        tileWidth = `${tileWidth}px`;
                    } else if (!tileWidth || tileWidth === '') {
                        tileWidth = '200px'; // Fallback default
                    }

                    const styles = {
                        '--min-tile-width': tileWidth,
                        '--content-layout': this.contentLayout
                    };
                    console.log('[Content Panel] Applying styles:', styles);
                    return styles;
                }
                return {};
            },

            styles() {
                return {};
            },

        };
    },
};
