/**
 * Cube Loader Component
 * Reusable loading animation for AJAX operations
 * 
 * Usage:
 * 1. Include cube-loader.css in your page
 * 2. Include cube-loader.js in your page
 * 3. Call CubeLoader.show('Your message') to show loader
 * 4. Call CubeLoader.hide() to hide loader
 */

const CubeLoader = {
    loader: null,
    minDisplayTime: 2000, // 2 seconds minimum
    startTime: null,

    /**
     * Initialize the loader (creates DOM element if not exists)
     */
    init: function() {
        if (!this.loader) {
            this.loader = document.createElement('div');
            this.loader.id = 'cube-loader';
            this.loader.className = 'loader-overlay';
            this.loader.innerHTML = `
                <div class="cube-wrapper">
                    <div class="cube-folding">
                        <span class="leaf1"></span>
                        <span class="leaf2"></span>
                        <span class="leaf3"></span>
                        <span class="leaf4"></span>
                    </div>
                    <span class="loading" id="cube-loader-text">Loading...</span>
                </div>
            `;
            document.body.appendChild(this.loader);
        }
    },

    /**
     * Show the loader with custom message
     * @param {string} message - Loading message to display
     * @param {number} minTime - Minimum display time in milliseconds (default: 2000)
     */
    show: function(message = 'Loading...', minTime = null) {
        this.init();
        this.startTime = Date.now();
        if (minTime !== null) {
            this.minDisplayTime = minTime;
        }
        
        const textElement = document.getElementById('cube-loader-text');
        if (textElement) {
            textElement.textContent = message;
        }
        
        this.loader.classList.add('active');
    },

    /**
     * Hide the loader (respects minimum display time)
     * @param {function} callback - Optional callback to execute after hiding
     */
    hide: function(callback) {
        if (!this.loader || !this.startTime) {
            if (callback) callback();
            return;
        }

        const elapsedTime = Date.now() - this.startTime;
        const remainingTime = Math.max(0, this.minDisplayTime - elapsedTime);

        setTimeout(() => {
            this.loader.classList.remove('active');
            this.startTime = null;
            if (callback) callback();
        }, remainingTime);
    },

    /**
     * Update the loading message while loader is visible
     * @param {string} message - New message to display
     */
    updateMessage: function(message) {
        const textElement = document.getElementById('cube-loader-text');
        if (textElement) {
            textElement.textContent = message;
        }
    },

    /**
     * Set minimum display time
     * @param {number} time - Time in milliseconds
     */
    setMinDisplayTime: function(time) {
        this.minDisplayTime = time;
    }
};

// Auto-initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        CubeLoader.init();
    });
} else {
    CubeLoader.init();
}
