<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // DBファサードをインポート
use App\Models\Item; // Itemモデルをインポート
use App\Models\User; // Userモデルをインポート
use App\Models\Category; // Categoryモデルをインポート

class ItemsSeeder extends Seeder
{
    public function run()
    {
        // 最初のカテゴリを取得
        $category = Category::first();
        // 最初のユーザーを取得
        $user = User::first(); // 最初のユーザーを取得（適切なユーザーがいることを確認）

        // 商品データの配列を作成
        $items = [
            [
                'item_name' => '腕時計',
                'price' => 15000,
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'item_image' => 'Armani+Mens+Clock.jpg',
                'status' => '良好',
            ],
            [
                'item_name' => 'HDD',
                'price' => 5000,
                'description' => '高速で信頼性の高いハードディスク',
                'item_image' => 'HDD+Hard+Disk.jpg',
                'status' => '目立った傷や汚れなし',
            ],
            [
                'item_name' => '玉ねぎ3束',
                'price' => 300,
                'description' => '新鮮な玉ねぎ3束のセット',
                'item_image' => 'iLoveIMG+d.jpg',
                'status' => 'やや傷や汚れあり',
            ],
            [
                'item_name' => '革靴',
                'price' => 4000,
                'description' => 'クラシックなデザインの革靴',
                'item_image' => 'Leather+Shoes+Product+Photo.jpg',
                'status' => '状態が悪い',
            ],
            [
                'item_name' => 'ノートPC',
                'price' => 45000,
                'description' => '高性能なノートパソコン',
                'item_image' => 'Living+Room+Laptop.jpg',
                'status' => '良好',
            ],
            [
                'item_name' => 'マイク',
                'price' => 8000,
                'description' => '高音質のレコーディング用マイク',
                'item_image' => 'Music+Mic+4632231.jpg',
                'status' => '目立った傷や汚れなし',
            ],
            [
                'item_name' => 'ショルダーバッグ',
                'price' => 3500,
                'description' => 'おしゃれなショルダーバッグ',
                'item_image' => 'Purse+fashion+pocket.jpg',
                'status' => 'やや傷や汚れあり',
            ],
            [
                'item_name' => 'タンブラー',
                'price' => 500,
                'description' => '使いやすいタンブラー',
                'item_image' => 'Tumbler+souvenir.jpg',
                'status' => '状態が悪い',
            ],
            [
                'item_name' => 'コーヒーミル',
                'price' => 4000,
                'description' => '手動のコーヒーミル',
                'item_image' => 'Waitress+with+Coffee+Grinder.jpg',
                'status' => '良好',
            ],
            [
                'item_name' => 'メイクセット',
                'price' => 2500,
                'description' => '便利なメイクアップセット',
                'item_image' => '外出メイクアップセット.jpg',
                'status' => '目立った傷や汚れなし',
            ]
        ];

        // 商品データを挿入
        foreach ($items as $itemData) {
            // 商品をItemテーブルに挿入
            $item = Item::create([
                'item_name' => $itemData['item_name'],
                'price' => $itemData['price'],
                'description' => $itemData['description'],
                'status' => $itemData['status'],
                'user_id' => $user->id, // ユーザーIDを関連付ける
                'categories' => json_encode([$category->id]), // カテゴリIDをJSON形式で格納
              
            ]);

            // 商品の画像をitem_imagesテーブルに挿入
            DB::table('item_images')->insert([
                'item_id' => $item->id, // 商品IDを関連付け
                'item_image' => $itemData['item_image'], // 商品画像URL
            ]);
        }
    }
}
