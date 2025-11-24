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
    const ALLOWED_OPERATORS = ['+', '-', '*', '/', '(', ')'];
    const ALLOWED_FUNCTIONS = ['min', 'max', 'abs', 'ceil', 'floor', 'round', 'sqrt', 'pow'];

    public function validate($formula, array $variables = [])
    {
        $errors = [];
        
        if (empty($formula) || !is_string($formula)) {
            $errors[] = 'Formula must be a non-empty string';
            return ['valid' => false, 'errors' => $errors];
        }

        $dangerousPatterns = [
            '/\beval\b/i',
            '/\bexec\b/i',
            '/\bsystem\b/i',
            '/\bshell_exec\b/i',
            '/\bpassthru\b/i',
            '/\b__\w+__\b/',
            '/\$\w+/',
            '/[;<>{}]/',
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

        $tokens = $this->tokenize($formula);
        
        if (empty($tokens)) {
            $errors[] = 'Formula is empty after tokenization';
            return ['valid' => false, 'errors' => $errors];
        }

        $tokenErrors = $this->validateTokens($tokens, $variables);
        if (!empty($tokenErrors)) {
            $errors = array_merge($errors, $tokenErrors);
        }

        if (!$this->hasBalancedParentheses($formula)) {
            $errors[] = 'Formula has unbalanced parentheses';
        }

        // Пропускаем testEvaluation чтобы избежать рекурсии
        // Instead do a quick syntax check only
        if (empty($errors)) {
            $sampleValues = [];
            foreach ($variables as $variable) {
                $sampleValues[$variable] = 10;
            }
            $result = $this->evaluateWithoutValidation($formula, $sampleValues);
            if (!$result['success']) {
                $errors[] = 'Formula evaluation failed: ' . $result['error'];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    public function evaluate($formula, array $variableValues)
    {
        $validation = $this->validate($formula, array_keys($variableValues));
        if (!$validation['valid']) {
            return [
                'result' => null,
                'success' => false,
                'error' => implode(', ', $validation['errors'])
            ];
        }

        return $this->evaluateWithoutValidation($formula, $variableValues);
    }

    private function evaluateWithoutValidation($formula, array $variableValues)
    {
        $expression = $formula;
        foreach ($variableValues as $variable => $value) {
            if (!is_numeric($value)) {
                return [
                    'result' => null,
                    'success' => false,
                    'error' => "Variable '{$variable}' must have a numeric value"
                ];
            }
            $expression = preg_replace('/\b' . preg_quote($variable, '/') . '\b/', (string)$value, $expression);
        }

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

        try {
            if (preg_match('/[^\d\s\+\-\*\/\(\)\.,]/', $expression)) {
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

    private function tokenize($formula)
    {
        preg_match_all('/\d+\.?\d*|\w+|[+\-*\/()]/', $formula, $matches);
        return $matches[0] ?? [];
    }

    private function validateTokens(array $tokens, array $expectedVariables)
    {
        $errors = [];
        $undeclaredVars = [];
        
        foreach ($tokens as $token) {
            if (is_numeric($token) || in_array($token, self::ALLOWED_OPERATORS)) {
                continue;
            }
            
            if (in_array($token, self::ALLOWED_FUNCTIONS)) {
                continue;
            }
        if (!in_array($token, $expectedVariables)) {
            $undeclaredVars[] = $token;
        }
    }

    if (!empty($undeclaredVars)) {
        $errors[] = 'Undeclared variables: ' . implode(', ', array_unique($undeclaredVars));
    }

    return $errors;
}

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

public function extractVariables($formula)
{
    $tokens = $this->tokenize($formula);
    $variables = [];
    
    foreach ($tokens as $token) {
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