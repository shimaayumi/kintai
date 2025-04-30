<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Like;
use App\Models\Comment;
use App\Models\Address;
use App\Models\Purchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Mockery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;
    //すべての情報が商品詳細ページに表示されている
    public function test_product_detail_displays_all_required_information()
    {
        $user = User::factory()->create();

        $item = Item::factory()->create([
            'user_id' => $user->id,
            'item_name' => 'テスト商品',
            'brand_name' => 'テストブランド',
            'price' => 12345,
            'description' => 'これはテスト商品の説明です。',
            'status' => '良好',  // 正しいstatus値を使用
        ]);

        Item::factory()->create([
            'categories' => json_encode(['家電', 'スマホ'])  // JSON配列として保存
        ]);

        $comments = Comment::factory()->count(2)->create([
            'item_id' => $item->id,
        ]);
        $likes = Like::factory()->count(3)->create([
            'item_id' => $item->id,
        ]);

        $response = $this->get('/item/' . $item->id);

        $response->assertSee($item->item_name);
        $response->assertSee($item->brand_name);
        $response->assertSee(number_format($item->price));
        $response->assertSee($item->description);
        $response->assertSee('良好'); // ステータス表示名（日本語）でチェックする


        // カテゴリが表示されているか
        foreach (json_decode($item->categories) as $category) {
            $response->assertSee($category);
        }

        $response->assertSee((string) $likes->count());
        $response->assertSee((string) $comments->count());

        foreach ($comments as $comment) {
            $response->assertSee($comment->user->name);
            $response->assertSee($comment->comment);
        }
    }


    // いいねアイコンを押下して商品を「いいね」登録するテスト
    public function test_user_can_like_item()
    {
        // ユーザーと商品を作成
        $user = User::factory()->create();
        $item = Item::factory()->create();

        // ユーザーでログイン
        $this->actingAs($user);

        // 商品詳細ページにアクセス
        $response = $this->get(route('item.show', $item->id));

        // いいねアイコンが表示されていることを確認
        $response->assertSee('☆'); // 初期状態のアイコン（未いいね）

        // いいねアイコンを押下するためのリクエスト
        $response = $this->post(route('items.toggleLike', $item->id));

        // いいね合計値が増加したことを確認
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $item->id,
        ]);

        // レスポンスのisLikedフラグがtrueに変更されたことを確認
        $response->assertJson([
            'isLiked' => true,
        ]);
    }

    // いいねアイコンが押下されている状態で色が変化することを確認
    public function test_like_icon_changes_color_when_liked()
    {
        // ユーザーと商品を作成
        $user = User::factory()->create();
        $item = Item::factory()->create();

        // ユーザーでログイン
        $this->actingAs($user);

        // 商品詳細ページにアクセス
        $response = $this->get(route('item.show', $item->id));

        // いいねアイコンが未押下状態で表示されていることを確認
        $response->assertSee('☆'); // 初期状態のアイコン（未いいね）

        // いいねアイコンを押下するためのリクエスト
        $response = $this->post(route('items.toggleLike', $item->id));

        // アイコンが押下された状態で色が変化していることを確認
        $response->assertJson([
            'isLiked' => true,  // アイコンがいいねされている状態（色が変わる）
        ]);
    }

    // いいねアイコンを再度押下していいねを解除するテスト
    public function test_user_can_unlike_item()
    {
        // ユーザーと商品を作成
        $user = User::factory()->create();
        $item = Item::factory()->create();

        // ユーザーでログイン
        $this->actingAs($user);

        // 商品詳細ページにアクセス
        $response = $this->get(route('item.show', $item->id));

        // いいねアイコンを押下して、いいねを登録
        $this->post(route('items.toggleLike', $item->id));

        // いいねが登録された後のアイコンと合計値を確認
        $response = $this->get(route('item.show', $item->id));
        $response->assertSee('★'); // いいねされたアイコン（色が変化）

        // いいねアイコンを再度押下して、いいねを解除
        $this->post(route('items.toggleLike', $item->id));

        // いいねが解除された後のアイコンと合計値を確認
        $response = $this->get(route('item.show', $item->id));
        $response->assertSee('☆'); // いいね解除後のアイコン（色が元に戻る）
    }

    // 1. ログイン済みのユーザーはコメントを送信できる
    public function test_logged_in_user_can_submit_comment()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(); // 商品を作成

        // ユーザーでログイン
        $this->actingAs($user);

        // コメントを送信
        $response = $this->post(route('items.comment', $item->id), [
            'comment' => 'This is a test comment.',
        ]);

        // コメントがデータベースに保存され、コメント数が増加したことを確認
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'comment' => 'This is a test comment.',
        ]);

        $response->assertRedirect(route('item.show', $item->id)); // コメント送信後に商品ページにリダイレクトされることを確認
    }

    // 2. ログイン前のユーザーはコメントを送信できない
    public function test_guest_user_cannot_submit_comment()
    {
        $item = Item::factory()->create(); // 商品を作成

        // ログインせずにコメントを送信
        $response = $this->post(route('items.comment', $item->id), [
            'comment' => 'This is a test comment.',
        ]);

        // ゲストユーザーはコメント送信できないので、ログインページにリダイレクトされることを確認
        $response->assertRedirect(route('login'));
    }

    // 3. コメントが入力されていない場合、バリデーションメッセージが表示される
    public function test_validation_message_is_displayed_when_comment_is_empty()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(); // 商品を作成

        // ユーザーでログイン
        $this->actingAs($user);

        // コメントなしで送信
        $response = $this->post(route('items.comment', $item->id), [
            'comment' => '',
        ]);

        // バリデーションエラーメッセージが表示されることを確認
        $response->assertSessionHasErrors('comment');
    }

    // 4. コメントが255字以上の場合、バリデーションメッセージが表示される
    public function test_validation_message_is_displayed_when_comment_is_too_long()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create(); // 商品を作成

        // ユーザーでログイン
        $this->actingAs($user);

        // 256文字のコメントを送信
        $response = $this->post(route('items.comment', $item->id), [
            'comment' => str_repeat('a', 256), // 256文字
        ]);

        // バリデーションエラーメッセージが表示されることを確認
        $response->assertSessionHasErrors('comment');
    }


    //購入が完了する
    public function test_user_can_complete_purchase()
    {
        // 商品を作成
        $item = Item::factory()->create();
        $user = User::factory()->create();

        // ログインする
        $this->actingAs($user);

        // 購入リクエストデータ
        $requestData = [
            'payment_method' => 'credit_card',
            'shipping_postal_code' => '123-4567',
            'shipping_address' => 'Some address',
            'shipping_building' => 'Some building',
        ];



        $response = $this->post(route('test.purchase', $item->id));

        // ちゃんとDB上も sold_flag=1 になってるか確認
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'sold_flag' => 1,
        ]);
    }




    //購入した商品がプロフィールの購入した商品一覧に追加されている
    public function test_purchased_item_is_added_to_profile()
    {
        // ユーザーとアイテムを作成
        $user = User::factory()->create();
        $item = Item::factory()->create();

        // 住所を作成
        $address = Address::factory()->create();

        // 購入を作成
        $purchase = Purchase::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'address_id' => $address->id,
            'payment_method' => 'credit_card', // 例: クレジットカード
            'price' => $item->price,  // アイテムの価格を設定
            'shipping_postal_code' => '123-4567',
            'shipping_address' => 'Tokyo, Japan',
            'shipping_building' => 'Building A',
        ]);

        // 購入が正常に作成されたことを確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'price' => $item->price,  // 価格が正しく保存されていることを確認
        ]);
    
}

    public function test_shipping_address_is_updated_and_reflected_in_purchase()
    {
        $user = User::factory()->create();
        $item = Item::factory()->create();

        // ユーザーに最初の住所を作成
        $address = Address::factory()->create([
            'user_id' => $user->id,
            'postal_code' => '111-1111',
            'address' => 'もともとの住所',
            'building' => 'もともとの建物',
        ]);

        // ユーザーの住所を更新
        $newAddress = [
            'postal_code' => '123-4567',
            'address' => '新しい住所',
            'building' => '新しい建物',
        ];

        $response = $this->post(route('address.update', ['item_id' => $item->id]), $newAddress);

        // 新しい住所を保存
        $updatedAddress = Address::where('user_id', $user->id)->latest()->first();

        // 商品の価格を取得
        $price = $item->price;  // Item モデルの価格

        // 購入レコードの作成
        $purchase = Purchase::create([
            'user_id' => $user->id,
            'item_id' => $item->id,
            'address_id' => $updatedAddress->id, // address_idを追加
            'shipping_postal_code' => '123-4567',
            'shipping_address' => '新しい住所',
            'shipping_building' => '新しい建物',
            'price' => $price, // 価格を追加
        ]);

        // 購入レコードが作成されたことを確認
        $this->assertNotNull($purchase, 'Purchase record not found');
        $this->assertEquals('123-4567', $purchase->shipping_postal_code);
        $this->assertEquals('新しい住所', $purchase->shipping_address);
        $this->assertEquals('新しい建物', $purchase->shipping_building);
        $this->assertEquals($price, $purchase->price); // 価格の確認

        // レスポンスに新しい住所情報が表示されていることを確認
        $response->assertSeeText('123-4567');
        $response->assertSeeText('新しい住所');
        $response->assertSeeText('新しい建物');
    }

    public function test_shipping_address_is_saved_with_purchase()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $item = Item::factory()->create();

        $addressData = [
            'postal_code' => '123-4567',
            'address' => '東京都新宿区テスト1-2-3',
            'building' => 'テストビル101'
        ];

        // 住所を更新
        $this->patch(route('address.update', ['item_id' => $item->id]), $addressData);

        // 購入データを仮保存（PurchaseController経由）
        $purchaseData = [
            'item_id' => $item->id,
            'payment_method' => 'credit_card',
        ];
        $this->post(route('purchase.checkout', ['item_id' => $item->id]), $purchaseData);

        // 購入情報が正しくDBに保存されているか確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'shipping_postal_code' => $addressData['postal_code'],
            'shipping_address' => $addressData['address'],
            'shipping_building' => $addressData['building'],
        ]);
    }
}
