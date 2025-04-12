<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id(); // id カラム (unsigned bigint, primary key)
            $table->foreignId('user_id')->constrained('users'); // usersテーブルのidを外部キーとして参照
            $table->string('item_name')->nullable();
            $table->string('brand_name')->nullable(); // brand_name (nullable string)
            $table->text('description'); // description (text)
            $table->unsignedInteger('price'); // price (unsigned integer)
            $table->enum('status', ['良好', '目立った傷や汚れなし', 'やや傷や汚れあり', '状態が悪い']); // status (enum)
            $table->boolean('sold_flag')->default(0); // sold_flag (boolean)
            $table->json('categories')->nullable(); // 複数カテゴリを格納するJSONカラム
            $table->timestamps(); // created_at, updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('items');
    }
}
