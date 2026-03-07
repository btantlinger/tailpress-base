import Alpine from 'alpinejs'
/* ---------------------------------------------------------- */
/*  Helpers                                                   */
/* ---------------------------------------------------------- */
let STRATEGIES = null;

function loadStrategiesOnce() {
    if (STRATEGIES) {
        return STRATEGIES; // already cached
    }

    const modules = import.meta.glob('./strategies/*.js', { eager: true });
    const map = {};

    for (const [path, mod] of Object.entries(modules)) {
        const name = path.split('/').pop().replace('.js', '');
        map[name] = mod.default ?? mod[name] ?? Object.values(mod)[0];
    }

    STRATEGIES = Object.freeze(map); // cache & protect
    return STRATEGIES;
}

function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func.apply(this, args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func.apply(this, args);
    };
}

function generateUUID() {
    return 'nav-item-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
}

export const navMenuData = function(userConfig = {}) {

    const defaultConfig = {
        rootNavSelector: '.wm-nav__list',
        strategy: 'nested-list',
        maxDepth: 5,
        hasMobileMenu: false,
        mobileBreakpoint: 768,
        initialTitle: 'Main Menu',
        animationDuration: 300,
    };

    const config = {...defaultConfig, ...userConfig};

    return {
        // --- Core State ---

        config,
        strategy: config.strategy,
        mobileOpen: false,
        isMobile: window.innerWidth <= config.mobileBreakpoint,
        maxDepth: config.maxDepth,
        hasMobileMenu: config.hasMobileMenu,
        mobileMenuData: [],
        viewStack: [],
        currentView: {title: config.initialTitle, items: [], level: 0},
        stagedView: null, // Holds the view data for the incoming animation
        navigationDirection: 'forward', // 'forward' or 'back'
        animating: false, // Flag to prevent multiple animations,


        init() {
            console.log('Nav init with config:', this.config);
            console.log('Element:', this.$el);
            console.log('hasMobileMenu:', this.hasMobileMenu);
            console.log('Initial currentView:', this.currentView);
            console.log('Initial mobileMenuData:', this.mobileMenuData);
            console.log('Nav init with config:', this.config);
            /* Mark the element so children can discover it */
            this.$el.setAttribute('data-nav-menu', '');
            // Apply the root strategy class for default behavior and mobile menu
            this.$el.classList.add('wm-nav--' + this.strategy);

            // Expose default strategy for descendants to read
            this.$el.setAttribute('data-default-strategy', this.strategy);

            // Parse mobile navigation if needed
            if (this.hasMobileMenu && this.mobileMenuData.length === 0) {
                this.parseMobileNavigation();
            }


            this.checkMobile(); // Initial check
            window.addEventListener(
                'resize',
                debounce(() => this.checkMobile(), 150)
            );



            this.$watch('mobileOpen', val => {
                document.body.style.overflow = val ? 'hidden' : '';
                if (!val) { // Menu is closing
                    // Delay reset slightly to allow closing animation to finish
                    setTimeout(() => this.resetMobileNavigation(), 350); // Match transition duration + small buffer
                } else { // Menu is opening
                    this.resetMobileNavigation(); // Reset immediately on open
                    // Ensure positions are correct AFTER Alpine renders based on resetNavigation
                    this.$nextTick(() => this.resetMobileContainerPositions());
                }
            });
            // Initial position setup needs to happen after initial render
            this.$nextTick(() => this.resetMobileContainerPositions());
        },


        parseMobileNavigation() {
            console.log('=== parseMobileNavigation called ===');
            console.log('rootNavSelector:', this.config.rootNavSelector);

            let navElement = null;

            if (this.config.rootNavSelector) {
                navElement = this.$el.querySelector(this.config.rootNavSelector);
                console.log('Found navElement with selector:', navElement);
            } else {
                navElement = this.$el.querySelector('ul');
                console.log('Found navElement with ul:', navElement);
            }

            if (navElement) {
                console.log('navElement children:', navElement.children);
                console.log('navElement HTML:', navElement.innerHTML.substring(0, 200) + '...');

                this.mobileMenuData = this.parseMobileNavNode(navElement);
                console.log('Parsed menu data:', this.mobileMenuData);
            } else {
                console.warn('No navigation list found within the component.');
                this.mobileMenuData = [];
            }
        },

        parseMobileNavNode(node) {
            const items = [];
            if (!node || !node.children) {
                return items;
            }

            for (let i = 0; i < node.children.length; i++) {
                const li = node.children[i];

                if (!li || li.tagName !== 'LI') {
                    continue;
                }

                const linkElement = li.querySelector('a.wm-nav__link');
                if (!linkElement) {
                    continue;
                }

                const labelElement = li.querySelector('.wm-nav__label');
                if (!labelElement) {
                    continue;
                }

                try {
                    const href = linkElement.getAttribute('href') || '#';
                    const label = labelElement.getAttribute('data-label') || labelElement.textContent.trim();

                    const item = {
                        id: generateUUID(),
                        originalId: linkElement.getAttribute('data-id'),
                        label: label,
                        link: href,
                        active: li.classList.contains('wm-nav__item--active'),
                        children: []
                    };

                    const nestedUl = li.querySelector('ul.wm-nav__list');
                    if (nestedUl) {
                        item.children = this.parseMobileNavNode(nestedUl);
                    }

                    items.push(item);

                } catch (e) {
                    console.error(`AlpineNav: Could not parse a menu item.`, { error: e, element: li });
                }
            }

            return items;
        },


        // --- Animation Helper (using Animate.css) ---
        animateCSS(element, animation, duration = 300) { // Added duration
            return new Promise((resolve, reject) => { // Added reject
                const animationName = `animate__${animation}`;
                element.style.setProperty('--animate-duration', `${duration}ms`);
                element.classList.add('animate__animated', animationName);

                function handleAnimationEnd(event) {
                    event.stopPropagation();
                    element.classList.remove('animate__animated', animationName);
                    element.style.removeProperty('--animate-duration');
                    // Ensure listener is removed
                    element.removeEventListener('animationend', handleAnimationEnd);
                    element.removeEventListener('animationcancel', handleAnimationCancel); // Also handle cancel
                    resolve('Animation completed');
                }

                function handleAnimationCancel(event) { // Handle cancellation
                    event.stopPropagation();
                    element.classList.remove('animate__animated', animationName);
                    element.style.removeProperty('--animate-duration');
                    element.removeEventListener('animationend', handleAnimationEnd);
                    element.removeEventListener('animationcancel', handleAnimationCancel);
                    reject(new Error('Animation cancelled'));
                }

                element.addEventListener('animationend', handleAnimationEnd, {once: true});
                element.addEventListener('animationcancel', handleAnimationCancel, {once: true}); // Listen for cancel
            });
        },


        checkMobile() {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth <= 768;
            // If switching from mobile to desktop, close the mobile menu
            if (wasMobile && !this.isMobile) {
                this.mobileOpen = false; // This will trigger the $watch and reset
            }
        },

        toggleMobileMenu() {
            if (this.hasMobileMenu) {
                this.mobileOpen = !this.mobileOpen;
            }
            // Reset is handled by the $watch('mobileOpen', ...)
        },

        async navigateMobileMenuTo(detail) {
            const nextItems = Array.isArray(detail?.items) ? detail.items : [];
            // Use the JS maxDepth variable now
            if (this.animating || this.currentView.level >= (this.maxDepth - 1) || nextItems.length === 0) {
                if (this.currentView.level >= (this.maxDepth - 1) || nextItems.length === 0) {
                    console.warn('Navigation prevented: Max depth reached or no children.');
                }
                return;
            }
            this.animating = true;
            this.navigationDirection = 'forward';


            this.stagedView = {title: detail.title, items: nextItems, level: detail.level}; // Ensure all parts are copied


            await this.$nextTick();

            // Animate both containers simultaneously
            const currentContainer = this.$refs.currentContainer;
            const stagingContainer = this.$refs.stagingContainer;

            // Ensure refs are valid before animating
            if (!currentContainer || !stagingContainer) {
                console.error('Navigation error: Container refs not found.');
                // Attempt recovery (similar to catch block)
                this.viewStack.push(JSON.parse(JSON.stringify(this.currentView)));
                this.currentView = this.stagedView;
                this.stagedView = null;
                this.resetMobileContainerPositions(); // Try resetting positions
                this.animating = false;
                return;
            }

            // Make staging container visible for animation (it starts offset)
            stagingContainer.classList.remove('hidden');

            try {
                await Promise.all([
                    this.animateCSS(currentContainer, 'slideOutLeft'),
                    this.animateCSS(stagingContainer, 'slideInRight')
                ]);
            } catch (error) {
                console.error('Forward Animation failed:', error);
                // Fallback: Directly switch views if animation fails
                this.viewStack.push(JSON.parse(JSON.stringify(this.currentView)));
                this.currentView = this.stagedView;
                this.stagedView = null;
                this.resetMobileContainerPositions(); // Reset positions on failure
                this.animating = false;
                return;
            }

            // 4. Update the main view after animation
            this.viewStack.push(JSON.parse(JSON.stringify(this.currentView))); // Deep clone previous view
            this.currentView = this.stagedView;
            this.stagedView = null; // Clear the stage

            // 5. Reset container positions for the next navigation
            this.resetMobileContainerPositions();

            this.animating = false;
        },

        async navigateMobileMenuBack() {
            if (this.animating || this.viewStack.length === 0) {
                if (this.viewStack.length === 0) {
                    console.warn('Navigation back prevented: View stack is empty.');
                }
                return;
            }
            this.animating = true;
            this.navigationDirection = 'back';

            // 1. Prepare the staged view data (the previous view from stack)
            this.stagedView = this.viewStack[this.viewStack.length - 1];

            // 2. Wait for Alpine to render the staged view
            await this.$nextTick();

            // 3. Animate
            const currentContainer = this.$refs.currentContainer;
            const stagingContainer = this.$refs.stagingContainer;

            // Ensure refs are valid
            if (!currentContainer || !stagingContainer) {
                console.error('Navigation error: Container refs not found.');
                // Attempt recovery
                this.currentView = this.viewStack.pop(); // Go back in data immediately
                this.stagedView = null;
                this.resetMobileContainerPositions();
                this.animating = false;
                return;
            }

            stagingContainer.classList.remove('hidden'); // Make it visible for animation

            try {
                await Promise.all([
                    this.animateCSS(currentContainer, 'slideOutRight'),
                    this.animateCSS(stagingContainer, 'slideInLeft')
                ]);
            } catch (error) {
                console.error('Back Animation failed:', error);
                // Fallback: Set current view to the one we intended to go back TO
                this.currentView = this.stagedView; // stagedView holds the target (previous) view
                this.viewStack.pop(); // Remove the view we just navigated to from the stack
                this.stagedView = null;
                this.resetMobileContainerPositions(); // Reset positions on failure
                this.animating = false;
                return;
            }

            // Update the main view
            this.currentView = this.viewStack.pop(); // Pop the old view from stack, currentView is now the target (previous) view
            this.stagedView = null;

            // Reset positions
            this.resetMobileContainerPositions();

            this.animating = false;
        },

        resetMobileContainerPositions() {
            // Use try/catch as refs might not exist if called too early/late
            try {
                const currentContainer = this.$refs.currentContainer;
                const stagingContainer = this.$refs.stagingContainer;

                if (currentContainer) {
                    // Reset current container (should be visible, no transform)
                    currentContainer.style.transform = '';
                    currentContainer.classList.remove('hidden'); // Ensure it's visible
                }

                if (stagingContainer) {
                    // Reset staging container (should be hidden and offset for the *next* forward move)
                    stagingContainer.classList.add('hidden'); // Hide until needed
                    stagingContainer.style.transform = 'translateX(100%)'; // Reset transform
                }
            } catch (e) {
                console.warn('Error resetting container positions, refs might not be available yet.', e);
            }
        },

        resetMobileNavigation() {
            this.viewStack = [];
            // THIS IS THE BUG - config is not defined here, should be this.config
            this.currentView = {title: this.config.initialTitle, items: this.mobileMenuData, level: 0};
            this.stagedView = null;
            this.navigationDirection = 'forward';
            this.animating = false;
            this.$nextTick(() => this.resetMobileContainerPositions());
        },

        itemHasChildren(item) {
            // Ensure item and item.children exist and children is an array with elements
            console.log(item);
            return item && Array.isArray(item.children) && item.children.length > 0;
        }
    };
}

// Find nearest effective strategy by walking ancestors; fallback to root default
function resolveEffectiveStrategy(el, explicit) {
    if (explicit) return explicit;

    // Walk up to nearest ancestor item with Alpine data exposing itemStrategy
    let parentItem = el.closest('.wm-nav__item')?.parentElement?.closest('.wm-nav__item');
    while (parentItem) {
        const alpine = parentItem._x_dataStack?.[0];
        const s = alpine?.itemStrategy;
        if (s) return s;
        parentItem = parentItem.parentElement?.closest('.wm-nav__item');
    }

    // Fallback to root default strategy from the host nav
    const rootNav = el.closest('[data-nav-menu]');
    const defaultStrategy = rootNav?.getAttribute('data-default-strategy');
    return defaultStrategy || 'nested-list';
}

export const navItemData = function(userConfig = {}) {

    console.log('[navItem] Received userConfig:', JSON.parse(JSON.stringify(userConfig)));

    const defaultConfig = {
        level: 0,
        strategy: null, // allow inheritance
        anchorElementId: null,
        matchWidthOfAnchor: false,
        minTileWidth: 200, // Default minimum tile width
    };
    const config = {...defaultConfig, ...userConfig};

    // Attribute override if present
    const attrStrategy = this?.$el?.getAttribute?.('data-strategy');
    if (attrStrategy) {
        config.strategy = attrStrategy;
    }

    // Decide effective strategy (self -> ancestor -> root default)
    const effectiveStrategy = resolveEffectiveStrategy(this.$el, config.strategy);

    const strategies = loadStrategiesOnce();
    const activeStrategy = strategies[effectiveStrategy] ?? Object.values(strategies)[0];
    const originalHandler = activeStrategy.navItemHandler({...config, strategy: effectiveStrategy});

    return {
        ...originalHandler,

        // Expose effective strategy for descendants
        itemStrategy: effectiveStrategy,

        // Ensure per-item strategy class is present for CSS targeting
        classes() {
            const originalClasses = originalHandler.classes?.call(this) || '';
            const strategyClass = `wm-nav__item--strategy-${effectiveStrategy}`;
            return `${originalClasses} ${strategyClass}`.trim();
        }
    };
}

/*
document.addEventListener('alpine:init', () => {
    Alpine.data('navMenu', navMenuData);
    Alpine.data('navItem',  navItemData);
});
*/
