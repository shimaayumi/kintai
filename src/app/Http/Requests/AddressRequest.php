<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判断します。
     *
     * @return bool
     */
    public function authorize()
    {
        // 住所フォームのリクエストは、全てのユーザーに許可
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
            'name' => 'required|string', // お名前は必須、文字列
            'postal_code' => 'required|regex:/^\d{3}-\d{4}$/', // 郵便番号は必須、ハイフンありの8文字（XXX-XXXX）
            'address' => 'required|string', // 住所は必須、文字列
            'building' => 'required|string', // 建物名は必須、文字列
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
            'name.required' => 'お名前は必須です。',
            'postal_code.required' => '郵便番号は必須です。',
            'postal_code.regex' => '郵便番号はハイフンありの8文字（XXX-XXXX）の形式で入力してください。',
            'address.required' => '住所は必須です。',
            'building.required' => '建物名は必須です。',
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
