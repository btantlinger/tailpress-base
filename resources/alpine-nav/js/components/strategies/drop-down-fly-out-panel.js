import {baseNavItemHandler} from './base.js';

export default {

    navItemHandler(userConfig = {}) {
        const defaults = {
            level: 0,
            openDelay: 100,
            closeDelay: 200,
            matchWidthOf: null,
            anchorElementId: null,
            matchWidthOfAnchor: false,
            minTileWidth: 200,
            verticalMenuWidth: 300, // Default vertical menu width
        };

        const cfg = {...defaults, ...userConfig};

        return {
            ...baseNavItemHandler,

            // Override timing configuration
            openDelay: cfg.openDelay,
            closeDelay: cfg.closeDelay,

            // Strategy-specific properties
            level: cfg.level,
            matchWidthOf: cfg.matchWidthOf,
            anchorElementId: cfg.anchorElementId,
            matchWidthOfAnchor: cfg.matchWidthOfAnchor,
            minTileWidth: cfg.minTileWidth,
            verticalMenuWidth: cfg.verticalMenuWidth,

            // Override executeOpen
            executeOpen() {
                this.desktopOpen = true;
                this.$nextTick(() => {
                    this.ready = true;
                });
            },

            init() {
                // Alpine Anchor in HTML handles positioning for all levels

                // For flyout strategy, we handle width matching ourselves, so skip base handler
                if (this.matchWidthOfAnchor && this.anchorElementId && this.level === 0) {
                    // Custom flyout width matching
                    this.$watch('desktopOpen', (isOpen) => {
                        if (isOpen) {
                            this.$nextTick(() => {
                                this.applyFlyoutWidthMatching();
                            });
                        }
                    });

                    // Setup cleanup (copied from base handler)
                    this.$el.addEventListener('alpine:destroying', () => {
                        this.cleanupWidthMatching();
                    });
                } else {
                    // For non-width-matching flyouts, call base init
                    baseNavItemHandler.init.call(this);
                }

                // For level-1 items, watch for open state to position level-2 flyout
                if (this.level === 1) {
                    this.$watch('desktopOpen', (isOpen) => {
                        if (isOpen) {
                            this.$nextTick(() => {
                                this.positionLevel2Flyout();
                            });
                        }
                    });
                }
            },

            // Flyout-specific width matching: split anchor width between vertical menu and flyout panel
            applyFlyoutWidthMatching() {
                const anchorElement = document.querySelector(this.anchorElementId);
                const submenu = this.$el.querySelector('.wm-nav__submenu');

                if (!anchorElement || !submenu) return;

                const anchorWidth = anchorElement.offsetWidth;
                const flyoutWidth = anchorWidth - this.verticalMenuWidth;

                // Set width for all level-2 flyout panels
                const level1Items = submenu.querySelectorAll('.wm-nav__item--level-1');
                level1Items.forEach(item => {
                    const level2Submenu = item.querySelector('.wm-nav__submenu');
                    if (level2Submenu) {
                        level2Submenu.style.width = `${flyoutWidth}px`;
                    }
                });

                // Watch for resize
                if ('ResizeObserver' in window) {
                    const resizeObserver = new ResizeObserver(() => {
                        const newAnchorWidth = anchorElement.offsetWidth;
                        const newFlyoutWidth = newAnchorWidth - this.verticalMenuWidth;

                        level1Items.forEach(item => {
                            const level2Submenu = item.querySelector('.wm-nav__submenu');
                            if (level2Submenu) {
                                level2Submenu.style.width = `${newFlyoutWidth}px`;
                            }
                        });
                    });

                    resizeObserver.observe(anchorElement);
                }
            },

            // Position level-2 flyout aligned with level-0 container top
            // Since we disabled Alpine Anchor for level-1, we do all positioning here
            positionLevel2Flyout() {
                if (this.level !== 1) return;

                const submenu = this.$el.querySelector('.wm-nav__submenu');
                const level0Container = this.$el.closest('.wm-nav__item--level-0 > .wm-nav__submenu');

                if (!submenu || !level0Container) {
                    return;
                }

                // Get viewport dimensions
                const viewportWidth = window.innerWidth;

                // Get container dimensions
                const containerRect = level0Container.getBoundingClientRect();

                // Simple approach: position to the right of container, aligned to top
                submenu.style.position = 'absolute';
                submenu.style.zIndex = '40';
                submenu.style.display = 'block';
                submenu.style.visibility = 'visible';
                submenu.style.opacity = '1';

                // Position to right of vertical menu
                submenu.style.left = `${containerRect.width}px`;

                // Align to top of vertical menu (relative to level-0 container)
                submenu.style.top = '0px';

                // Match height of vertical menu
                submenu.style.minHeight = `${containerRect.height}px`;

                console.log('[Flyout] Before setting styles, computed values:');
                const computed = window.getComputedStyle(submenu);
                console.log('[Flyout] Computed position:', computed.position);
                console.log('[Flyout] Computed left:', computed.left);
                console.log('[Flyout] Computed top:', computed.top);
                console.log('[Flyout] Computed transform:', computed.transform);

                console.log('[Flyout] ========== DEBUG ==========');
                console.log('[Flyout] Container rect:', containerRect);
                console.log('[Flyout] Container rect:', this.verticalMenuWidth);
                console.log('[Flyout] Container.right:', containerRect.right);
                console.log('[Flyout] Submenu width:', submenuWidth);
                console.log('[Flyout] Viewport width:', viewportWidth);
                console.log('[Flyout] Calculated leftPos:', leftPos);
                console.log('[Flyout] Would overflow?', (containerRect.right + submenuWidth) > viewportWidth);
                console.log('[Flyout] Final position - left:', leftPos, 'top:', containerRect.top);
                console.log('[Flyout] ============================');
            },

            // Control when submenu should be shown via x-show
            shouldShowSubmenu() {
                // Levels 0 and 1: controlled by hover state
                // Level 2+: always visible (inline per CSS)
                if (this.level <= 1) {
                    return this.desktopOpen;
                }
                return true;
            },

            classes() {
                const classList = [];

                if (this.desktopOpen) classList.push('wm-nav__item--active');
                if (!!this.matchWidthOf && this.level === 0) {
                    classList.push('wm-nav__item--full-width');
                    classList.push('wm-nav__item--match-width');
                }

                return classList.join(' ');
            },

            submenuStyles() {
                const styles = {};

                // Level 0: Apply vertical menu width
                if (this.level === 0) {
                    styles.width = this.verticalMenuWidth + "px";
                }

                // Level 2+: Apply min tile width for grid
                if (this.level >= 2) {
                    let tileWidth = this.minTileWidth;
                    if (typeof tileWidth === 'number') {
                        tileWidth = `${tileWidth}px`;
                    } else if (!tileWidth || tileWidth === '') {
                        tileWidth = '200px'; // Fallback default
                    }
                    styles['--min-tile-width'] = tileWidth;
                }
                return styles;
            },


            submenuStyles1() {
                // Only apply custom property for level-0 (main panel)
                if (this.level === 0) {
                    // Ensure minTileWidth has units - add 'px' if it's just a number
                    let tileWidth = this.minTileWidth;
                    if (typeof tileWidth === 'number') {
                        tileWidth = `${tileWidth}px`;
                    } else if (!tileWidth || tileWidth === '') {
                        tileWidth = '200px';
                    }

                    const styles = {
                        '--min-tile-width': tileWidth
                    };
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