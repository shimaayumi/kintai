<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_全ての商品が表示される()
    {
        $items = Item::factory()->count(3)->create();

        $response = $this->get('/');

        foreach ($items as $item) {
            $response->assertSee($item->item_name);
        }
    }
    

    public function test_購入済みの商品には_sold_ラベルが表示される()
    {
        $user = User::factory()->create();

        // 購入された商品を作成
        $item = Item::factory()->create([
            'sold_flag' => true,
        ]);

        $response = $this->actingAs($user)->get('/');

        // 正確にHTMLとして確認（falseを渡してHTMLタグもチェック）
        $response->assertSee('<div class="sold-label">SOLD</div>', false);
    }

    /** @test */
    public function 自分が出品した商品は一覧に表示されない()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 自分の商品
        $ownItem = Item::factory()->create(['user_id' => $user->id]);
        // 他人の商品
        $otherItem = Item::factory()->create();

        $response = $this->get(route('index'));

        $response->assertDontSee($ownItem->item_name);
        $response->assertSee($otherItem->item_name);
    }
    
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'price' => $this->faker->numberBetween(1000, 10000),
            'user_id' => User::factory(),
        ];
    }
}