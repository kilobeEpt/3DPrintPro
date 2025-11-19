<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\FormulaValidatorService;

class FormulaValidatorServiceTest extends TestCase
{
    private $validator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new FormulaValidatorService();
    }
    
    public function testValidateSimpleFormula()
    {
        $result = $this->validator->validate('2 + 2', []);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testValidateFormulaWithVariables()
    {
        $result = $this->validator->validate('weight * price', ['weight', 'price']);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testValidateFormulaWithUndeclaredVariable()
    {
        $result = $this->validator->validate('weight * price', ['weight']);
        $this->assertFalse($result['valid']);
        $this->assertContains('Undeclared variables: price', $result['errors']);
    }
    
    public function testValidateFormulaWithMathFunctions()
    {
        $result = $this->validator->validate('max(10, min(20, value))', ['value']);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testValidateFormulaWithDivision()
    {
        $result = $this->validator->validate('total / quantity', ['total', 'quantity']);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testValidateFormulaWithParentheses()
    {
        $result = $this->validator->validate('(weight + 10) * (price - 5)', ['weight', 'price']);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testRejectFormulaWithEval()
    {
        $result = $this->validator->validate('eval("dangerous")', []);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }
    
    public function testRejectFormulaWithSystemCalls()
    {
        $result = $this->validator->validate('system("ls")', []);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }
    
    public function testRejectFormulaWithPHPVariables()
    {
        $result = $this->validator->validate('$variable * 2', []);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }
    
    public function testRejectFormulaWithUnbalancedParentheses()
    {
        $result = $this->validator->validate('(weight + 10', ['weight']);
        $this->assertFalse($result['valid']);
        $this->assertContains('Formula has unbalanced parentheses', $result['errors']);
    }
    
    public function testEvaluateSimpleFormula()
    {
        $result = $this->validator->evaluate('2 + 2', []);
        $this->assertTrue($result['success']);
        $this->assertEquals(4, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateFormulaWithVariables()
    {
        $result = $this->validator->evaluate('weight * price', [
            'weight' => 10,
            'price' => 5
        ]);
        $this->assertTrue($result['success']);
        $this->assertEquals(50, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateFormulaWithDecimal()
    {
        $result = $this->validator->evaluate('0.3 + (infill / 100 * 0.7)', ['infill' => 20]);
        $this->assertTrue($result['success']);
        $this->assertEquals(0.44, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateFormulaWithMathFunctions()
    {
        $result = $this->validator->evaluate('min(10, value)', ['value' => 15]);
        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateFormulaWithMax()
    {
        $result = $this->validator->evaluate('max(10, value)', ['value' => 5]);
        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateFormulaWithCeil()
    {
        $result = $this->validator->evaluate('ceil(value / 8)', ['value' => 15]);
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateFormulaWithFloor()
    {
        $result = $this->validator->evaluate('floor(value / 2)', ['value' => 15]);
        $this->assertTrue($result['success']);
        $this->assertEquals(7, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateFormulaWithRound()
    {
        $result = $this->validator->evaluate('round(value)', ['value' => 3.7]);
        $this->assertTrue($result['success']);
        $this->assertEquals(4, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateFormulaWithSqrt()
    {
        $result = $this->validator->evaluate('sqrt(value)', ['value' => 16]);
        $this->assertTrue($result['success']);
        $this->assertEquals(4, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateFormulaWithPow()
    {
        $result = $this->validator->evaluate('pow(value, 2)', ['value' => 3]);
        $this->assertTrue($result['success']);
        $this->assertEquals(9, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateComplexFormula()
    {
        $result = $this->validator->evaluate(
            '500 + (weight * 2)',
            ['weight' => 100]
        );
        $this->assertTrue($result['success']);
        $this->assertEquals(700, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateInfillFactorFormula()
    {
        $result = $this->validator->evaluate(
            '0.3 + (infill / 100 * 0.7)',
            ['infill' => 50]
        );
        $this->assertTrue($result['success']);
        $this->assertEquals(0.65, $result['result']);
        $this->assertNull($result['error']);
    }
    
    public function testEvaluateFailsWithNonNumericVariable()
    {
        $result = $this->validator->evaluate('weight * 2', ['weight' => 'invalid']);
        $this->assertFalse($result['success']);
        $this->assertNotNull($result['error']);
    }
    
    public function testExtractVariables()
    {
        $variables = $this->validator->extractVariables('weight * price + tax');
        $this->assertEquals(['weight', 'price', 'tax'], $variables);
    }
    
    public function testExtractVariablesIgnoresNumbers()
    {
        $variables = $this->validator->extractVariables('weight * 10 + 5');
        $this->assertEquals(['weight'], $variables);
    }
    
    public function testExtractVariablesIgnoresOperators()
    {
        $variables = $this->validator->extractVariables('(weight + 10) * (price - 5)');
        $this->assertEquals(['weight', 'price'], $variables);
    }
    
    public function testExtractVariablesIgnoresFunctions()
    {
        $variables = $this->validator->extractVariables('max(weight, min(10, height))');
        $this->assertEquals(['weight', 'height'], $variables);
    }
}
