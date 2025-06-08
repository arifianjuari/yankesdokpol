<?php
/**
 * Validation Functions File
 * 
 * This file contains functions for form validation and data integrity checks.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

/**
 * Validate form data
 * 
 * @param array $data Form data to validate
 * @param array $rules Validation rules
 * @return array Validation results with errors
 */
function validateForm($data, $rules) {
    $errors = [];
    
    // This function will be implemented to validate form data
    // based on specified rules
    
    return $errors;
}

/**
 * Validate file upload
 * 
 * @param array $file $_FILES array element
 * @param array $allowedTypes Allowed MIME types
 * @param int $maxSize Maximum file size in bytes
 * @return array Validation results with errors
 */
function validateFileUpload($file, $allowedTypes, $maxSize) {
    $errors = [];
    
    // This function will be implemented to validate file uploads
    // checking file type, size, and potential errors
    
    return $errors;
}

/**
 * Validate date format
 * 
 * @param string $date Date string to validate
 * @param string $format Expected date format
 * @return bool True if valid, false otherwise
 */
function validateDateFormat($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate phone number format
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validatePhoneNumber($phone) {
    // This function will be implemented to validate Indonesian phone numbers
    
    return false;
}

/**
 * Check required fields
 * 
 * @param array $data Form data
 * @param array $requiredFields List of required field names
 * @return array Missing field names
 */
function checkRequiredFields($data, $requiredFields) {
    $missing = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    
    return $missing;
}

/**
 * Validate image dimensions
 * 
 * @param string $imagePath Path to image
 * @param int $minWidth Minimum width
 * @param int $minHeight Minimum height
 * @return bool True if valid, false otherwise
 */
function validateImageDimensions($imagePath, $minWidth, $minHeight) {
    // This function will be implemented to validate image dimensions
    
    return false;
}

/**
 * Sanitize and validate array of form data
 * 
 * @param array $data Form data
 * @return array Sanitized data
 */
function sanitizeFormData($data) {
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        $sanitized[$key] = sanitizeInput($value);
    }
    
    return $sanitized;
}
