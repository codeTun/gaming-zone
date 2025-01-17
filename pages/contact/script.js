// Form handling with enhanced feedback
const contactForm = document.getElementById('contact_form');
const contactSuccessMessage = document.getElementById('contact_success-message');

contactForm.addEventListener('submit', (e) => {
    e.preventDefault();
    
    // Disable form while submitting
    const formElements = contactForm.elements;
    const submitButton = contactForm.querySelector('.contact_submit-btn');
    
    // Add loading state
    submitButton.innerHTML = '<span>Sending...</span>';
    submitButton.disabled = true;
    
    // Disable all form inputs
    Array.from(formElements).forEach(element => {
        element.disabled = true;
    });
    
    // Simulate form submission with loading state
    setTimeout(() => {
        // Reset form and enable inputs
        contactForm.reset();
        Array.from(formElements).forEach(element => {
            element.disabled = false;
        });
        
        // Reset button state
        submitButton.innerHTML = '<span>Send Message</span>';
        submitButton.disabled = false;
        
        // Show success message
        contactSuccessMessage.style.display = 'block';
        
        // Hide success message after delay
        setTimeout(() => {
            contactSuccessMessage.style.display = 'none';
        }, 5000);
    }, 1500);
});