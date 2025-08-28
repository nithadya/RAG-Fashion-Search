/**
 * Feedback Widget - Floating feedback form for better user experience
 * Allows users to submit feedback from any page
 */

class FeedbackWidget {
    constructor(options = {}) {
        this.options = {
            apiEndpoint: options.apiEndpoint || 'api/feedback.php',
            position: options.position || 'bottom-right',
            showAfterTime: options.showAfterTime || 5000,
            pulseInterval: options.pulseInterval || 30000,
            ...options
        };
        
        this.isOpen = false;
        this.currentUser = null;
        this.init();
    }

    init() {
        this.createWidget();
        this.attachEventListeners();
        this.checkUserAuth();
        
        // Show widget after specified time
        setTimeout(() => {
            this.showWidget();
        }, this.options.showAfterTime);
        
        // Pulse animation periodically
        if (this.options.pulseInterval > 0) {
            setInterval(() => {
                if (!this.isOpen) {
                    this.pulseWidget();
                }
            }, this.options.pulseInterval);
        }
    }

    createWidget() {
        // Create main widget HTML
        const widgetHTML = `
            <div class="feedback-widget" id="feedbackWidget">
                <button class="feedback-btn" id="feedbackBtn">
                    <i class="fas fa-comment"></i>
                    <span>Feedback</span>
                </button>
            </div>

            <div class="feedback-modal" id="feedbackModal">
                <div class="feedback-modal-content">
                    <div class="feedback-modal-header">
                        <h3 class="feedback-modal-title">
                            <i class="fas fa-heart text-danger"></i>
                            Share Your Feedback
                        </h3>
                        <button class="feedback-close-btn" id="feedbackCloseBtn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div id="feedbackMessages"></div>

                    <form class="feedback-form" id="feedbackForm">
                        <div class="feedback-form-group">
                            <label class="feedback-form-label">Feedback Type</label>
                            <div class="feedback-type-options">
                                <div class="feedback-type-option">
                                    <input type="radio" name="type" value="feedback" id="typeFeedback" class="feedback-type-radio" checked>
                                    <label for="typeFeedback" class="feedback-type-label">
                                        <i class="fas fa-star"></i> Feedback
                                    </label>
                                </div>
                                <div class="feedback-type-option">
                                    <input type="radio" name="type" value="suggestion" id="typeSuggestion" class="feedback-type-radio">
                                    <label for="typeSuggestion" class="feedback-type-label">
                                        <i class="fas fa-lightbulb"></i> Suggestion
                                    </label>
                                </div>
                                <div class="feedback-type-option">
                                    <input type="radio" name="type" value="complaint" id="typeComplaint" class="feedback-type-radio">
                                    <label for="typeComplaint" class="feedback-type-label">
                                        <i class="fas fa-exclamation-triangle"></i> Issue
                                    </label>
                                </div>
                                <div class="feedback-type-option">
                                    <input type="radio" name="type" value="contact" id="typeContact" class="feedback-type-radio">
                                    <label for="typeContact" class="feedback-type-label">
                                        <i class="fas fa-envelope"></i> Contact
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="feedback-form-group">
                            <label for="feedbackName" class="feedback-form-label">Your Name</label>
                            <input type="text" id="feedbackName" class="feedback-form-input" placeholder="Enter your name" required>
                        </div>

                        <div class="feedback-form-group">
                            <label for="feedbackEmail" class="feedback-form-label">Your Email</label>
                            <input type="email" id="feedbackEmail" class="feedback-form-input" placeholder="Enter your email" required>
                        </div>

                        <div class="feedback-form-group">
                            <label for="feedbackSubject" class="feedback-form-label">Subject</label>
                            <input type="text" id="feedbackSubject" class="feedback-form-input" placeholder="Brief subject line" required>
                        </div>

                        <div class="feedback-form-group">
                            <label for="feedbackMessage" class="feedback-form-label">Your Message</label>
                            <textarea id="feedbackMessage" class="feedback-form-textarea" 
                                placeholder="Tell us what you think, what can be improved, or ask any questions..." 
                                required></textarea>
                            <small style="color: #6c757d; margin-top: 5px;">Minimum 10 characters</small>
                        </div>

                        <button type="submit" class="feedback-submit-btn" id="feedbackSubmitBtn">
                            <i class="fas fa-paper-plane"></i>
                            Send Feedback
                        </button>
                    </form>
                </div>
            </div>
        `;

        // Add to page
        document.body.insertAdjacentHTML('beforeend', widgetHTML);
    }

    attachEventListeners() {
        const feedbackBtn = document.getElementById('feedbackBtn');
        const feedbackModal = document.getElementById('feedbackModal');
        const feedbackCloseBtn = document.getElementById('feedbackCloseBtn');
        const feedbackForm = document.getElementById('feedbackForm');

        // Open modal
        feedbackBtn.addEventListener('click', () => this.openModal());

        // Close modal
        feedbackCloseBtn.addEventListener('click', () => this.closeModal());

        // Close on outside click
        feedbackModal.addEventListener('click', (e) => {
            if (e.target === feedbackModal) {
                this.closeModal();
            }
        });

        // Handle form submission
        feedbackForm.addEventListener('submit', (e) => this.handleSubmit(e));

        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.closeModal();
            }
        });

        // Auto-update subject based on type
        const typeRadios = document.querySelectorAll('input[name="type"]');
        typeRadios.forEach(radio => {
            radio.addEventListener('change', () => this.updateSubjectPlaceholder(radio.value));
        });
    }

    showWidget() {
        const widget = document.getElementById('feedbackWidget');
        widget.style.opacity = '1';
        widget.style.transform = 'translateY(0)';
    }

    pulseWidget() {
        const btn = document.getElementById('feedbackBtn');
        btn.classList.add('pulse');
        setTimeout(() => {
            btn.classList.remove('pulse');
        }, 2000);
    }

    openModal() {
        const modal = document.getElementById('feedbackModal');
        modal.classList.add('show');
        this.isOpen = true;
        
        // Focus on first input
        setTimeout(() => {
            document.getElementById('feedbackName').focus();
        }, 300);
    }

    closeModal() {
        const modal = document.getElementById('feedbackModal');
        modal.classList.remove('show');
        this.isOpen = false;
        this.clearMessages();
    }

    async checkUserAuth() {
        try {
            const response = await fetch('api/auth.php?action=check');
            const data = await response.json();
            
            if (data.success && data.loggedIn) {
                this.currentUser = data.user;
                // Pre-fill user info if logged in
                document.getElementById('feedbackName').value = data.user.name || '';
                document.getElementById('feedbackEmail').value = data.user.email || '';
            }
        } catch (error) {
            console.log('Could not check auth status:', error);
        }
    }

    updateSubjectPlaceholder(type) {
        const subjectInput = document.getElementById('feedbackSubject');
        const placeholders = {
            feedback: 'Share your thoughts about our website',
            suggestion: 'Your suggestion for improvement',
            complaint: 'Describe the issue you encountered', 
            contact: 'What would you like to discuss?'
        };
        
        subjectInput.placeholder = placeholders[type] || 'Brief subject line';
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('feedbackSubmitBtn');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.innerHTML = '<div class="feedback-spinner"></div> Sending...';
        submitBtn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('name', document.getElementById('feedbackName').value);
            formData.append('email', document.getElementById('feedbackEmail').value);
            formData.append('subject', document.getElementById('feedbackSubject').value);
            formData.append('message', document.getElementById('feedbackMessage').value);
            formData.append('type', document.querySelector('input[name="type"]:checked').value);

            const response = await fetch(this.options.apiEndpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showMessage('success', data.message || 'Thank you for your feedback!');
                document.getElementById('feedbackForm').reset();
                
                // Close modal after 2 seconds
                setTimeout(() => {
                    this.closeModal();
                }, 2000);
            } else {
                this.showMessage('error', data.message || 'Failed to send feedback. Please try again.');
            }

        } catch (error) {
            this.showMessage('error', 'Network error. Please try again.');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    showMessage(type, message) {
        const messagesDiv = document.getElementById('feedbackMessages');
        const messageClass = type === 'success' ? 'feedback-success-message' : 'feedback-error-message';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        messagesDiv.innerHTML = `
            <div class="${messageClass}">
                <i class="fas ${icon}"></i>
                ${message}
            </div>
        `;
    }

    clearMessages() {
        document.getElementById('feedbackMessages').innerHTML = '';
    }
}

// Auto-initialize if CSS is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Check if feedback widget CSS is loaded
    const cssLoaded = Array.from(document.styleSheets).some(sheet => {
        try {
            return sheet.href && sheet.href.includes('feedback-widget.css');
        } catch (e) {
            return false;
        }
    });

    if (cssLoaded || document.querySelector('.feedback-widget')) {
        // Initialize feedback widget
        window.feedbackWidget = new FeedbackWidget({
            showAfterTime: 3000, // Show after 3 seconds
            pulseInterval: 45000 // Pulse every 45 seconds
        });
    }
});

// Export for manual initialization
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FeedbackWidget;
}
