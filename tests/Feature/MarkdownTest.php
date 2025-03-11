<?php


use App\Models\Post;
use App\Models\User;
use App\Services\LinkData;
use App\Services\LinkService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
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
