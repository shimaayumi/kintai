<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判断します。
     *
     * @return bool
     */
    public function authorize()
    {
        // 購入フォームのリクエストは、全てのユーザーに許可
        return true;
    }

    /**
     * リクエストに適用するバリデーションルールを取得します。
     *
     * @return array
     */
    public function rules()
    {
        return [
            'payment_method' => 'required|string', // 支払い方法は必須、文字列
            'shipping_address' => 'required|string', // 配送先は必須、文字列
        ];
    }

    /**
     * バリデーションエラーメッセージをカスタマイズする。
     *
     * @return array
     */
    public function messages()
    {
        return [
            'payment_method.required' => '支払い方法は必須です。',
            'shipping_address.required' => '配送先は必須です。',
        ];
    }

    /**
     * バリデーション後に使用するデータを取得します。
     *
     * @return array
     */
    public function validated()
    {
        return parent::validated();
    }
}
