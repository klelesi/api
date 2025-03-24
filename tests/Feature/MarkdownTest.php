<?php


use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_html_for_markdown()
    {
        $user = User::factory()->create();

        $data = ['markdown' => '#Hello'];

        $response = $this->actingAs($user, 'sanctum')->postJson(route('markdown.preview'), $data)->assertStatus(200);
        $html = $response->json('data.html');

        $this->assertSame('<h1>Hello</h1>', $html);
    }
}
