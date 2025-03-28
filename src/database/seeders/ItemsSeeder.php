<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item; // Itemモデルをインポート

class ItemsSeeder extends Seeder
{
    public function run()
    {
        // 商品データを挿入
        Item::create([
            'user_id' => 1,  // 適切な user_id に変更
            'category_id' => 1,  // 適切な category_id に変更
            'items_name' => '腕時計',  // 'name' ではなく 'items_name' を使用
            'brand_name' => 'アーニーメンズ',
            'description' => 'スタイリッシュなデザインのメンズ腕時計',
            'price' => 15000,
            'status' => 'new',
            'sold_flag' => 0,
        ]);

        Item::create([
            'user_id' => 1,  // 適切な user_id に変更
            'category_id' => 1,  // 適切な category_id に変更
            'items_name' => 'HDD',
            'brand_name' => 'ブランド名未設定',
            'description' => '高速で信頼性の高いハードディスク',
            'price' => 5000,
            'status' => 'used',
            'sold_flag' => 0,
        ]);

        Item::create([
            'user_id' => 1,  // 適切な user_id に変更
            'category_id' => 2,  // 適切な category_id に変更
            'items_name' => '玉ねぎ3束',
            'brand_name' => '農家直送',
            'description' => '新鮮な玉ねぎ3束のセット',
            'price' => 300,
            'status' => 'new',
            'sold_flag' => 0,
        ]);

        Item::create([
            'user_id' => 1,  // 適切な user_id に変更
            'category_id' => 3,  // 適切な category_id に変更
            'items_name' => '革靴',
            'brand_name' => '老舗ブランド',
            'description' => 'クラシックなデザインの革靴',
            'price' => 4000,
            'status' => 'used',
            'sold_flag' => 0,
        ]);

        Item::create([
            'user_id' => 1,  // 適切な user_id に変更
            'category_id' => 4,  // 適切な category_id に変更
            'items_name' => 'ノートPC',
            'brand_name' => '高性能PCブランド',
            'description' => '高性能なノートパソコン',
            'price' => 45000,
            'status' => 'new',
            'sold_flag' => 0,
        ]);
    }
}