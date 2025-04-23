<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id(); 

            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('address_id')->constrained('addresses');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // usersテーブルの外部キーとしてuser_idを追加
            $table->enum('payment_method', ['credit_card', 'convenience_store']); 
            $table->integer('price');
           
            $table->string('shipping_postal_code');
            $table->string('shipping_address');
            $table->string('shipping_building');
        
            $table->timestamps(); // created_at, updated_at
           
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['item_id']);    // item_id の外部キーを削除
            $table->dropForeign(['address_id']); // address_id の外部キーを削除
            $table->dropForeign(['user_id']);    // user_id の外部キーを削除
        });

        Schema::dropIfExists('purchases');
    }
}
