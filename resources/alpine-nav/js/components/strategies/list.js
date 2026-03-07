import { baseNavItemHandler } from './base.js';

/**
 * navMenu strategy for a simple non-interactive nested list.
*/
export default {
    navItemHandler(userConfig= {}) {
        return {
            ...baseNavItemHandler,

            level: userConfig.level ?? 0,

            init() {
                console.log('Nav item init with config:', userConfig);
            },

            // Override to disable delay functionality for non-interactive list
            openSubmenu() {
                // No-op for list strategy
            },

            closeSubmenu() {
                // No-op for list strategy  
            },

            submenuClasses() {
                return {}; // No open/ready states for list
            },
        }
    },
};
