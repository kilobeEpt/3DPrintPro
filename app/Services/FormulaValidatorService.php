<?php

namespace App\Services;

/**
 * Formula Validator Service
 * 
 * Validates and parses mathematical expressions for calculator formulas.
 * Ensures formulas are safe, syntactically correct, and contain only allowed operations.
 */
class FormulaValidatorService
{
    /**
     * Allowed operators in formulas
     */
    const ALLOWED_OPERATORS = ['+', '-', '*', '/', '(', ')'];
    
    /**
     * Allowed functions in formulas
     */
    const ALLOWED_FUNCTIONS = ['min', 'max', 'abs', 'ceil', 'floor', 'round', 'sqrt', 'pow'];
    
    /**
     * Validate a formula
     * 
     * @param string $formula The formula to validate
     * @param array $variables Expected variable names
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validate($formula, array $variables = [])
    {
        $errors = [];
        
        if (empty($formula) || !is_string($formula)) {
            $errors[] = 'Formula must be a non-empty string';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check for dangerous functions/keywords
        $dangerousPatterns = [
            '/\beval\b/i',
            '/\bexec\b/i',
            '/\bsystem\b/i',
            '/\bshell_exec\b/i',
            '/\bpassthru\b/i',
            '/\b__\w+__\b/', // Magic methods
            '/\$\w+/', // PHP variables
            '/[;<>{}]/', // Statement separators and comparison operators
            '/\binclude\b/i',
            '/\brequire\b/i',
            '/\bfile_\w+\b/i',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $formula)) {
                $errors[] = 'Formula contains disallowed operations or keywords';
                return ['valid' => false, 'errors' => $errors];
            }
        }
        
        // Tokenize the formula
        $tokens = $this->tokenize($formula);
        
        if (empty($tokens)) {
            $errors[] = 'Formula is empty after tokenization';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Validate tokens
        $tokenErrors = $this->validateTokens($tokens, $variables);
        if (!empty($tokenErrors)) {
            $errors = array_merge($errors, $tokenErrors);
        }
        
        // Check for balanced parentheses
        if (!$this->hasBalancedParentheses($formula)) {
            $errors[] = 'Formula has unbalanced parentheses';
        }
        
        // Try to evaluate with sample data
        if (empty($errors)) {
            $evalResult = $this->testEvaluation($formula, $variables);
            if (!$evalResult['valid']) {
                $errors = array_merge($errors, $evalResult['errors']);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Evaluate a formula with given variable values
     * 
     * @param string $formula The formula to evaluate
     * @param array $variableValues Variable name => value mappings
     * @return array ['result' => mixed, 'success' => bool, 'error' => string|null]
     */
    public function evaluate($formula, array $variableValues)
    {
        // Validate formula first
        $validation = $this->validate($formula, array_keys($variableValues));
        if (!$validation['valid']) {
            return [
                'result' => null,
                'success' => false,
                'error' => implode(', ', $validation['errors'])
            ];
        }
        
        // Replace variables with values
        $expression = $formula;
        foreach ($variableValues as $variable => $value) {
            if (!is_numeric($value)) {
                return [
                    'result' => null,
                    'success' => false,
                    'error' => "Variable '{$variable}' must have a numeric value"
                ];
            }
            // Use word boundary to avoid partial replacements
            $expression = preg_replace('/\b' . preg_quote($variable, '/') . '\b/', (string)$value, $expression);
        }
        
        // Replace function names with PHP equivalents
        $functionMap = [
            'min' => 'min',
            'max' => 'max',
            'abs' => 'abs',
            'ceil' => 'ceil',
            'floor' => 'floor',
            'round' => 'round',
            'sqrt' => 'sqrt',
            'pow' => 'pow',
        ];
        
        foreach ($functionMap as $formulaFunc => $phpFunc) {
            $expression = preg_replace('/\b' . $formulaFunc . '\b/', $phpFunc, $expression);
        }
        
        // Evaluate the expression safely
        try {
            // Additional safety check before eval
            if (preg_match('/[^\d\s\+\-\*\/\(\)\.,]/', $expression)) {
                // Check if remaining characters are allowed functions
                $cleanedExpression = preg_replace('/\b(' . implode('|', array_values($functionMap)) . ')\b/', '', $expression);
                if (preg_match('/[^\d\s\+\-\*\/\(\)\.,]/', $cleanedExpression)) {
                    return [
                        'result' => null,
                        'success' => false,
                        'error' => 'Expression contains invalid characters after variable substitution'
                    ];
                }
            }
            
            $result = @eval("return {$expression};");
            
            if ($result === false) {
                return [
                    'result' => null,
                    'success' => false,
                    'error' => 'Failed to evaluate expression'
                ];
            }
            
            return [
                'result' => $result,
                'success' => true,
                'error' => null
            ];
        } catch (\Throwable $e) {
            return [
                'result' => null,
                'success' => false,
                'error' => 'Evaluation error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Tokenize a formula into components
     * 
     * @param string $formula
     * @return array
     */
    private function tokenize($formula)
    {
        // Match numbers, variables, operators, and functions
        preg_match_all('/\d+\.?\d*|\w+|[+\-*\/()]/', $formula, $matches);
        return $matches[0] ?? [];
    }
    
    /**
     * Validate tokens
     * 
     * @param array $tokens
     * @param array $expectedVariables
     * @return array Errors
     */
    private function validateTokens(array $tokens, array $expectedVariables)
    {
        $errors = [];
        $undeclaredVars = [];
        
        foreach ($tokens as $token) {
            // Skip numbers and operators
            if (is_numeric($token) || in_array($token, self::ALLOWED_OPERATORS)) {
                continue;
            }
            
            // Check if it's an allowed function
            if (in_array($token, self::ALLOWED_FUNCTIONS)) {
                continue;
            }
            
            // Must be a variable - check if it's declared
            if (!in_array($token, $expectedVariables)) {
                $undeclaredVars[] = $token;
            }
        }
        
        if (!empty($undeclaredVars)) {
            $errors[] = 'Undeclared variables: ' . implode(', ', array_unique($undeclaredVars));
        }
        
        return $errors;
    }
    
    /**
     * Check if formula has balanced parentheses
     * 
     * @param string $formula
     * @return bool
     */
    private function hasBalancedParentheses($formula)
    {
        $count = 0;
        $length = strlen($formula);
        
        for ($i = 0; $i < $length; $i++) {
            if ($formula[$i] === '(') {
                $count++;
            } elseif ($formula[$i] === ')') {
                $count--;
                if ($count < 0) {
                    return false;
                }
            }
        }
        
        return $count === 0;
    }
    
    /**
     * Test evaluation with sample data
     * 
     * @param string $formula
     * @param array $variables
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function testEvaluation($formula, array $variables)
    {
        $errors = [];
        
        // Create sample values for all variables
        $sampleValues = [];
        foreach ($variables as $variable) {
            $sampleValues[$variable] = 10; // Use 10 as a safe test value
        }
        
        // Try to evaluate
        $result = $this->evaluate($formula, $sampleValues);
        
        if (!$result['success']) {
            $errors[] = 'Formula evaluation failed: ' . $result['error'];
        } elseif (!is_numeric($result['result'])) {
            $errors[] = 'Formula must evaluate to a numeric result';
        } elseif (!is_finite($result['result'])) {
            $errors[] = 'Formula produces infinite or invalid result';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Extract variables from a formula
     * 
     * @param string $formula
     * @return array Variable names found in the formula
     */
    public function extractVariables($formula)
    {
        $tokens = $this->tokenize($formula);
        $variables = [];
        
        foreach ($tokens as $token) {
            // Skip numbers, operators, and functions
            if (is_numeric($token) || 
                in_array($token, self::ALLOWED_OPERATORS) ||
                in_array($token, self::ALLOWED_FUNCTIONS)) {
                continue;
            }
            
            $variables[] = $token;
        }
        
        return array_unique($variables);
    }
}
