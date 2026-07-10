<?php

namespace Tests\Feature;

use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentFormattedContentTest extends TestCase
{
    use RefreshDatabase;

    public function testMentionsAreHighlighted(): void
    {
        $comment = new Comment(['content' => 'Hello @johndoe how are you?']);

        $formatted = $comment->formatted_content;

        $this->assertStringContainsString('bg-sky-100', $formatted);
        $this->assertStringContainsString('@johndoe', $formatted);
        $this->assertStringContainsString('<span', $formatted);
    }

    public function testBoldFormattingIsApplied(): void
    {
        $comment = new Comment(['content' => 'This is **important** text']);

        $formatted = $comment->formatted_content;

        $this->assertStringContainsString('<strong', $formatted);
        $this->assertStringContainsString('important', $formatted);
        $this->assertStringNotContainsString('**', $formatted);
    }

    public function testItalicFormattingIsApplied(): void
    {
        $comment = new Comment(['content' => 'This is *emphasized* text']);

        $formatted = $comment->formatted_content;

        $this->assertStringContainsString('<em', $formatted);
        $this->assertStringContainsString('emphasized', $formatted);
    }

    public function testInlineCodeFormattingIsApplied(): void
    {
        $comment = new Comment(['content' => 'Run `composer install` to set up']);

        $formatted = $comment->formatted_content;

        $this->assertStringContainsString('<code', $formatted);
        $this->assertStringContainsString('composer install', $formatted);
        $this->assertStringContainsString('font-mono', $formatted);
    }

    public function testHtmlIsEscaped(): void
    {
        $comment = new Comment(['content' => '<script>alert("xss")</script>']);

        $formatted = $comment->formatted_content;

        $this->assertStringNotContainsString('<script>', $formatted);
        $this->assertStringContainsString('&lt;script&gt;', $formatted);
    }

    public function testCombinedFormatting(): void
    {
        $comment = new Comment(['content' => '@admin please run `deploy` command **now**']);

        $formatted = $comment->formatted_content;

        // Mention badge present
        $this->assertStringContainsString('bg-sky-100', $formatted);
        $this->assertStringContainsString('@admin', $formatted);

        // Code formatting present
        $this->assertStringContainsString('<code', $formatted);
        $this->assertStringContainsString('deploy', $formatted);

        // Bold present
        $this->assertStringContainsString('<strong', $formatted);
        $this->assertStringContainsString('now', $formatted);
    }

    public function testPlainTextRemainsUnchanged(): void
    {
        $comment = new Comment(['content' => 'Just a normal comment']);

        $formatted = $comment->formatted_content;

        $this->assertEquals('Just a normal comment', $formatted);
    }
}
