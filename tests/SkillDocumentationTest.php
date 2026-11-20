<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs;

use PHPUnit\Framework\TestCase;

/**
 * 随包发布的 SKILL.md 是使用方 AI 的直接输入，示例必须能被真实注解构造器接受。
 *
 * @internal
 * @coversNothing
 */
class SkillDocumentationTest extends TestCase
{
    public function testValidationAnnotationExamplesUseCorrectParameterName(): void
    {
        $content = file_get_contents(dirname(__DIR__) . '/publish/skills/api-docs/SKILL.md');
        $this->assertIsString($content);

        // 单个验证注解的构造参数是 message；只有通用 #[Validation] 使用 messages。
        $this->assertStringNotContainsString('Email(messages:', $content);
        $this->assertStringContainsString("#[Email(message: '邮箱格式不正确')]", $content);
        $this->assertDoesNotMatchRegularExpression('/#\[(?!Validation)\w+\(messages:/', $content);
    }
}
