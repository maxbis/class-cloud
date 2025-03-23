// Word Cloud Visualization
class WordCloud {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            width: container.clientWidth,
            height: container.clientHeight,
            padding: 10,
            minFontSize: 14,
            maxFontSize: 40,
            rotation: [-45, 45],
            colors: ['#3498db', '#2ecc71', '#e74c3c', '#f1c40f', '#9b59b6'],
            ...options
        };
        
        this.words = [];
        this.layout = [];
    }
    
    setWords(words) {
        this.words = words;
        this.layout = [];
        this.render();
    }
    
    render() {
        // Clear container
        this.container.innerHTML = '';
        
        // Sort words by frequency
        this.words.sort((a, b) => b.frequency - a.frequency);
        
        // Calculate font sizes
        const maxFreq = Math.max(...this.words.map(w => w.frequency));
        const minFreq = Math.min(...this.words.map(w => w.frequency));
        
        // Create word elements
        this.words.forEach(word => {
            const fontSize = this.calculateFontSize(word.frequency, minFreq, maxFreq);
            const color = this.getRandomColor();
            const rotation = this.getRandomRotation();
            
            const element = document.createElement('div');
            element.className = 'word-cloud-item';
            element.textContent = word.word;
            element.style.fontSize = `${fontSize}px`;
            element.style.color = color;
            element.style.transform = `rotate(${rotation}deg)`;
            
            // Position the word
            this.positionWord(element);
            
            this.container.appendChild(element);
        });
    }
    
    calculateFontSize(frequency, minFreq, maxFreq) {
        const range = this.options.maxFontSize - this.options.minFontSize;
        const freqRange = maxFreq - minFreq;
        return this.options.minFontSize + (frequency - minFreq) * range / freqRange;
    }
    
    getRandomColor() {
        return this.options.colors[Math.floor(Math.random() * this.options.colors.length)];
    }
    
    getRandomRotation() {
        const [min, max] = this.options.rotation;
        return Math.random() * (max - min) + min;
    }
    
    positionWord(element) {
        const rect = element.getBoundingClientRect();
        const containerRect = this.container.getBoundingClientRect();
        
        let x, y;
        let attempts = 0;
        const maxAttempts = 100;
        
        do {
            x = Math.random() * (containerRect.width - rect.width);
            y = Math.random() * (containerRect.height - rect.height);
            attempts++;
        } while (this.checkCollision(x, y, rect.width, rect.height) && attempts < maxAttempts);
        
        element.style.left = `${x}px`;
        element.style.top = `${y}px`;
        
        this.layout.push({
            element,
            x,
            y,
            width: rect.width,
            height: rect.height
        });
    }
    
    checkCollision(x, y, width, height) {
        return this.layout.some(item => {
            return !(x + width < item.x ||
                    x > item.x + item.width ||
                    y + height < item.y ||
                    y > item.y + item.height);
        });
    }
}

// Real-time Updates
class RealTimeUpdates {
    constructor(sessionId) {
        this.sessionId = sessionId;
        this.lastUpdate = 0;
        this.updateInterval = 5000; // 5 seconds
    }
    
    start() {
        this.checkUpdates();
        setInterval(() => this.checkUpdates(), this.updateInterval);
    }
    
    async checkUpdates() {
        try {
            const basePath = window.location.pathname.split('/class-cloud')[0] + '/class-cloud';
            const response = await fetch(`${basePath}/api/updates.php?session_id=${this.sessionId}&last_update=${this.lastUpdate}`);
            const data = await response.json();
            
            if (data.success && data.hasUpdates) {
                this.lastUpdate = data.timestamp;
                this.handleUpdates(data.updates);
            }
        } catch (error) {
            console.error('Error checking for updates:', error);
        }
    }
    
    handleUpdates(updates) {
        if (updates.bulletPoints) {
            this.updateBulletPoints(updates.bulletPoints);
        }
        if (updates.keywords) {
            this.updateWordCloud(updates.keywords);
        }
    }
    
    updateBulletPoints(bulletPoints) {
        const container = document.getElementById('bullet-points-container');
        if (!container) return;
        
        container.innerHTML = bulletPoints.map(point => `
            <div class="card fade-in">
                <div class="card-header">
                    <strong>${point.student_name}</strong>
                    <small>${new Date(point.created_at).toLocaleString()}</small>
                </div>
                <p>${point.content}</p>
            </div>
        `).join('');
    }
    
    updateWordCloud(keywords) {
        const container = document.getElementById('word-cloud');
        if (!container) return;
        
        if (!this.wordCloud) {
            this.wordCloud = new WordCloud(container);
        }
        
        this.wordCloud.setWords(keywords);
    }
}

// Form Validation
class FormValidator {
    static validateAccessCode(code) {
        return /^[A-Z0-9]{6}$/.test(code);
    }
    
    static validateName(name) {
        return name.length >= 2 && name.length <= 50;
    }
    
    static validateBulletPoint(content) {
        return content.length >= 3 && content.length <= 500;
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    // Initialize real-time updates if on a session page
    const sessionId = document.querySelector('meta[name="session-id"]')?.content;
    if (sessionId) {
        const realTime = new RealTimeUpdates(sessionId);
        realTime.start();
    }
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
            }
        });
    });
    
    // Word cloud initialization
    const wordCloudContainer = document.getElementById('word-cloud');
    if (wordCloudContainer) {
        const wordCloud = new WordCloud(wordCloudContainer);
        // Initial render will be triggered by real-time updates
    }
});

// Utility Functions
function showAlert(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} fade-in`;
    alert.textContent = message;
    
    const container = document.querySelector('.container');
    container.insertBefore(alert, container.firstChild);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleString();
}

// Export for use in other files
window.WordCloud = WordCloud;
window.RealTimeUpdates = RealTimeUpdates;
window.FormValidator = FormValidator; 