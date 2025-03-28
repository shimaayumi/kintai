<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // item_imagesテーブルの作成
        Schema::create('item_images', function (Blueprint $table) {
            $table->id(); // id カラム (unsigned bigint, primary key)
            $table->foreignId('item_id')->constrained('items'); // itemsテーブルのidを外部キーとして参照
            $table->string('image_path'); // image_path (string)
            $table->timestamps(); // created_at, updated_at
        });

        // profilesテーブルのprofile_imageカラムの変更部分を削除またはコメントアウト
        // Schema::table('profiles', function (Blueprint $table) {
        //     $table->string('profile_image')->default('default.png')->change();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // item_imagesテーブルを削除
        Schema::dropIfExists('item_images');

        // profilesテーブルのprofile_imageカラムの変更部分を削除またはコメントアウト
        // Schema::table('profiles', function (Blueprint $table) {
        //     $table->string('profile_image')->nullable()->change();
        // });
    }
}
