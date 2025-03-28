<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemLikesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_likes', function (Blueprint $table) {
            $table->id(); // id カラム (unsigned bigint, primary key)
            $table->foreignId('user_id')->constrained('users'); // usersテーブルのidを外部キーとして参照
            $table->foreignId('item_id')->constrained('items'); // itemsテーブルのidを外部キーとして参照
            $table->timestamps(); // created_at, updated_at

            $table->unique(['user_id', 'item_id']); // 同じユーザーが同じアイテムを複数回いいねできないように
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('item_likes');
    }
}
