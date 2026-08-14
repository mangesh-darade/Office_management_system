/**
 * Global Form Validation System
 * 
 * Provides reusable validation for mandatory fields across all forms
 * Usage: Add data-validate="true" to form and data-mandatory="true" to required fields
 */

(function() {
  'use strict';

  /**
   * Form Validation Class
   */
  class FormValidator {
    constructor(form) {
      this.form = form;
      this.mandatoryFields = [];
      this.validationRules = {};
      this.init();
    }

    /**
     * Initialize validator
     */
    init() {
      if (!this.form) return;

      // Find all mandatory fields:
      // - data-mandatory="true" (explicitly marked)
      // - .mandatory-field (CSS helper class)
      // - [required] (HTML5 required attribute)
      this.mandatoryFields = Array.from(
        this.form.querySelectorAll('[data-mandatory="true"], .mandatory-field, [required]')
      );

      // Setup validation rules
      this.setupRules();

      // Attach event listeners
      this.attachListeners();
    }

    /**
     * Setup validation rules for each field
     */
    setupRules() {
      this.mandatoryFields.forEach(field => {
        const fieldName = field.name || field.id;
        const fieldLabel = this.getFieldLabel(field);
        const tagName = (field.tagName || '').toLowerCase();
        
        // Get custom rules from data attributes
        let minLength = field.getAttribute('data-min-length');
        let maxLength = field.getAttribute('data-max-length');
        const pattern = field.getAttribute('data-pattern');
        const customMessage = field.getAttribute('data-error-message');

        // Defaults for different field types
        if (!minLength) {
          if (field.name === 'phone') {
            minLength = 10;
          } else if (tagName === 'select') {
            // For selects we only care about non-empty value, not length
            minLength = 1;
          } else {
            minLength = 2;
          }
        }
        if (!maxLength) {
          if (field.type === 'password' || tagName === 'textarea') {
            maxLength = 0;
          } else {
            maxLength = 255;
          }
        }

        this.validationRules[fieldName] = {
          field: field,
          label: fieldLabel,
          minLength: parseInt(minLength, 10),
          maxLength: parseInt(maxLength, 10),
          pattern: pattern ? new RegExp(pattern) : null,
          customMessage: customMessage,
          type: field.type || 'text',
          tag: tagName
        };
      });
    }

    /**
     * Get field label text
     */
    getFieldLabel(field) {
      // Try to find label associated with field
      const id = field.id;
      if (id) {
        const label = document.querySelector(`label[for="${id}"]`);
        if (label) {
          return label.textContent.replace(/\s*\*\s*$/, '').trim();
        }
      }

      // Try to find label in parent
      const parent = field.closest('.col-md-6, .col-md-12, .col-md-4, .col-md-3');
      if (parent) {
        const label = parent.querySelector('label');
        if (label) {
          return label.textContent.replace(/\s*\*\s*$/, '').trim();
        }
      }

      // Fallback to field name
      return field.name ? field.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Field';
    }

    /**
     * Attach event listeners
     */
    attachListeners() {
      // Validate on blur
      this.mandatoryFields.forEach(field => {
        field.addEventListener('blur', () => {
          this.validateField(field);
        });

        // Clear error on input
        field.addEventListener('input', () => {
          if (this.isFieldValid(field)) {
            this.clearError(field);
          }
        });
      });

      // Validate on form submit
      this.form.addEventListener('submit', (e) => {
        if (!this.validateForm()) {
          e.preventDefault();
          e.stopPropagation();
          return false;
        }
      });
    }

    /**
     * Validate single field
     */
    validateField(field) {
      const fieldName = field.name || field.id;
      const rule = this.validationRules[fieldName];
      
      if (!rule) return true;

      const value = field.value.trim();
      const tag = rule.tag || (field.tagName || '').toLowerCase();
      let isValid = true;
      let errorMessage = '';

      // Check if required
      if (value === '') {
        isValid = false;
        errorMessage = rule.customMessage || `${rule.label} is required.`;
      }
      // For SELECT elements, only required (non-empty) is enforced
      else if (tag === 'select') {
        // No length or pattern checks for dropdowns
      }
      // Check minimum length
      else if (value.length < rule.minLength) {
        isValid = false;
        errorMessage = rule.customMessage || 
          `${rule.label} must be at least ${rule.minLength} characters long.`;
      }
      // Check maximum length (0 = no cap — tokens, passwords, textareas)
      else if (rule.maxLength > 0 && value.length > rule.maxLength) {
        isValid = false;
        errorMessage = rule.customMessage || 
          `${rule.label} cannot exceed ${rule.maxLength} characters.`;
      }
      // Check pattern (for phone, email, etc.)
      else if (rule.pattern && !rule.pattern.test(value)) {
        isValid = false;
        if (field.name === 'phone') {
          errorMessage = 'Phone must contain only numbers, spaces, +, -, and parentheses.';
        } else {
          errorMessage = rule.customMessage || `${rule.label} format is invalid.`;
        }
      }
      // Special validation for phone
      else if (field.name === 'phone' && !/^[0-9+\s\-\(\)]+$/.test(value)) {
        isValid = false;
        errorMessage = 'Phone must contain only numbers, spaces, +, -, and parentheses.';
      }
      // Special validation for email
      else if (field.type === 'email' && value !== '' && !this.isValidEmail(value)) {
        isValid = false;
        errorMessage = 'Please enter a valid email address.';
      }

      if (isValid) {
        this.clearError(field);
      } else {
        this.showError(field, errorMessage);
      }

      return isValid;
    }

    /**
     * Validate entire form
     */
    validateForm() {
      let isValid = true;
      let firstInvalidField = null;

      this.mandatoryFields.forEach(field => {
        if (!this.validateField(field)) {
          isValid = false;
          if (!firstInvalidField) {
            firstInvalidField = field;
          }
        }
      });

      // Scroll to first error
      if (!isValid && firstInvalidField) {
        firstInvalidField.scrollIntoView({ 
          behavior: 'smooth', 
          block: 'center' 
        });
        firstInvalidField.focus();
      }

      return isValid;
    }

    /**
     * Show error message
     */
    showError(field, message) {
      // Remove existing error
      this.clearError(field);

      // Add invalid class
      field.classList.add('is-invalid');
      field.classList.remove('is-valid');

      // Create error message element
      const errorDiv = document.createElement('div');
      errorDiv.className = 'error-message text-danger small mt-1';
      errorDiv.textContent = message;
      errorDiv.setAttribute('role', 'alert');

      // Insert error message after field
      field.parentElement.appendChild(errorDiv);
    }

    /**
     * Clear error message
     */
    clearError(field) {
      field.classList.remove('is-invalid');
      
      const errorMessage = field.parentElement.querySelector('.error-message');
      if (errorMessage) {
        errorMessage.remove();
      }
    }

    /**
     * Check if field is valid
     */
    isFieldValid(field) {
      const fieldName = field.name || field.id;
      const rule = this.validationRules[fieldName];
      
      if (!rule) return true;

      const value = field.value.trim();
      
      if (value === '') return false;
      if (value.length < rule.minLength) return false;
      if (rule.maxLength > 0 && value.length > rule.maxLength) return false;
      if (rule.pattern && !rule.pattern.test(value)) return false;
      if (field.name === 'phone' && !/^[0-9+\s\-\(\)]+$/.test(value)) return false;
      if (field.type === 'email' && value !== '' && !this.isValidEmail(value)) return false;

      return true;
    }

    /**
     * Validate email format
     */
    isValidEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    }

    /**
     * Reset form validation
     */
    reset() {
      this.mandatoryFields.forEach(field => {
        this.clearError(field);
      });
    }
  }

  /**
   * Initialize all forms with validation
   */
  function initValidation() {
    // Find all forms with data-validate attribute
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    forms.forEach(form => {
      // Skip if already initialized
      if (form.dataset.validatorInitialized === 'true') {
        return;
      }

      // Create validator instance
      const validator = new FormValidator(form);
      
      // Mark as initialized
      form.dataset.validatorInitialized = 'true';
      
      // Store validator instance on form element
      form._validator = validator;
    });
  }

  /**
   * Manual initialization for specific form
   */
  window.initFormValidation = function(formElement) {
    if (typeof formElement === 'string') {
      formElement = document.querySelector(formElement);
    }
    
    if (formElement && formElement.tagName === 'FORM') {
      return new FormValidator(formElement);
    }
    
    return null;
  };

  /**
   * Validate specific form manually
   */
  window.validateForm = function(formSelector) {
    const form = typeof formSelector === 'string' 
      ? document.querySelector(formSelector) 
      : formSelector;
    
    if (form && form._validator) {
      return form._validator.validateForm();
    }
    
    return false;
  };

  /**
   * Reset form validation
   */
  window.resetFormValidation = function(formSelector) {
    const form = typeof formSelector === 'string' 
      ? document.querySelector(formSelector) 
      : formSelector;
    
    if (form && form._validator) {
      form._validator.reset();
    }
  };

  // Auto-initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initValidation);
  } else {
    initValidation();
  }

  // Re-initialize on dynamic content load
  if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver(function(mutations) {
      mutations.forEach(function(mutation) {
        if (mutation.addedNodes.length) {
          initValidation();
        }
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true
    });
  }

  // Export for use in other scripts
  window.FormValidator = FormValidator;

})();
