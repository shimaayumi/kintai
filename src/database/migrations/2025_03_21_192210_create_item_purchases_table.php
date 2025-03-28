<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id(); // id カラム (unsigned bigint, primary key)

            $table->foreignId('item_id')->constrained('items');
            $table->foreignId('address_id')->constrained('addresses');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // usersテーブルの外部キーとしてuser_idを追加
            $table->enum('payment_method', ['credit_card', 'paypal', 'bank_transfer']); // payment_method (enum)
            $table->integer('total_price'); // total_price (integer)
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
        Schema::dropIfExists('purchases');
    }
}
