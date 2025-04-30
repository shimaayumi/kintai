<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Item;
use App\Models\Like;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class MyListTest extends TestCase
{
    use RefreshDatabase;

    public function test_liked_items_are_displayed()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();
        Like::create(['user_id' => $user->id, 'item_id' => $item->id]);

        $response = $this->actingAs($user)->get('/?page=mylist');

        $response->assertStatus(200);
        $response->assertSee($item->item_name);
    }

    public function test_sold_label_displayed_for_purchased_items()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(['sold_flag' => true]);
        Like::create(['user_id' => $user->id, 'item_id' => $item->id]);

        $response = $this->actingAs($user)->get('/?page=mylist');

        $response->assertSee('SOLD');
    }

    public function test_own_item_is_not_displayed_in_recommended()
    {
        // ユーザーを作成
        $user = User::factory()->create();

        // 他のアイテムも作成
        $ownItem = Item::factory()->create(['user_id' => $user->id]);  // 自分が出品したアイテム
        $otherItem = Item::factory()->create(); // 他のアイテム

        // ユーザーをログイン
        $this->actingAs($user);

        // 商品一覧ページにアクセス
        $response = $this->get(route('index'));  // 商品一覧ページを確認

        // 自分が出品した商品が表示されないことを確認
        $response->assertDontSee($ownItem->item_name);
        
        // 他の商品が表示されていることを確認
        $response->assertSee($otherItem->item_name);
    }



    public function test_guest_sees_nothing_in_mylist()
    {
        $item = Item::factory()->create(['item_name' => 'テスト商品']);

        $response = $this->get('/?page=mylist');

        $response->assertStatus(200);
        $response->assertDontSee('テスト商品');
    }

    public function test_product_search_returns_matching_items()
    {
        // テスト用ユーザー＆商品作成
        $user = User::factory()->create();
        Item::factory()->create(['item_name' => 'レインボーキーホルダー']);
        Item::factory()->create(['item_name' => 'サングラス']);

        // 商品名の一部「レインボー」で検索
        $response = $this->actingAs($user)->get('/?keyword=レインボー');

        $response->assertStatus(200);
        $response->assertSee('レインボーキーホルダー');
        $response->assertDontSee('サングラス');
    }

    public function test_mylist_search_keeps_keyword_and_filters_items()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // 他ユーザーの商品を2つ用意（うち1つはいいね）
        $matchingItem = Item::factory()->create(['item_name' => 'ハンドメイドブレスレット', 'user_id' => $otherUser->id]);
        $nonMatchingItem = Item::factory()->create(['item_name' => 'スマホケース', 'user_id' => $otherUser->id]);

        // userがmatchingItemにいいねする
        Like::factory()->create(['user_id' => $user->id, 'item_id' => $matchingItem->id]);
        Like::factory()->create(['user_id' => $user->id, 'item_id' => $nonMatchingItem->id]);

        // 「ブレスレット」でマイリスト検索
        $response = $this->actingAs($user)->get('/?page=mylist&keyword=ブレスレット');

        $response->assertStatus(200);
        $response->assertSee('ハンドメイドブレスレット');
        $response->assertDontSee('スマホケース');
        $response->assertSee('ブレスレット'); // キーワード保持の確認
    }

}
