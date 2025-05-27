<?php
class ValidationHelper {
    
    // Validate email
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Validate password strength
    public static function password($password, $minLength = 6) {
        if (strlen($password) < $minLength) {
            return ['valid' => false, 'message' => "Password must be at least {$minLength} characters long"];
        }
        return ['valid' => true];
    }
    
    // Validate required fields
    public static function required($data, $fields) {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            return ['valid' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)];
        }
        
        return ['valid' => true];
    }
    
    // Validate enum values
    public static function enum($value, $allowedValues, $fieldName = 'field') {
        if (!in_array($value, $allowedValues)) {
            return ['valid' => false, 'message' => "Invalid {$fieldName}. Allowed values: " . implode(', ', $allowedValues)];
        }
        return ['valid' => true];
    }
    
    // Validate date format
    public static function date($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        if ($d && $d->format($format) === $date) {
            return ['valid' => true];
        }
        return ['valid' => false, 'message' => "Invalid date format. Expected: {$format}"];
    }
    
    // Validate integer range
    public static function intRange($value, $min = null, $max = null, $fieldName = 'value') {
        if (!is_numeric($value)) {
            return ['valid' => false, 'message' => "{$fieldName} must be a number"];
        }
        
        $intValue = (int)$value;
        
        if ($min !== null && $intValue < $min) {
            return ['valid' => false, 'message' => "{$fieldName} must be at least {$min}"];
        }
        
        if ($max !== null && $intValue > $max) {
            return ['valid' => false, 'message' => "{$fieldName} must be at most {$max}"];
        }
        
        return ['valid' => true];
    }
    
    // Sanitize input
    public static function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}
?>
