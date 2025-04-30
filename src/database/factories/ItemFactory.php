<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'item_name' => $this->faker->word,
            'price' => $this->faker->numberBetween(100, 10000),
            'description' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['良好', '目立った傷や汚れなし', 'やや傷や汚れあり', '状態が悪い']),
            'user_id' => \App\Models\User::factory(), // 関連ユーザーも一緒に生成
            'categories' => json_encode([1]), // とりあえずカテゴリID 1を仮で入れておく
            
        ];
    }
}
