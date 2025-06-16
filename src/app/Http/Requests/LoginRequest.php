<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判断します。
     *
     * @return bool
     */
    public function authorize()
    {
        // ログインフォームのリクエストは、全てのユーザーに許可
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
            'email' => 'required|email',
            'password' => 'required|string|min:8', // パスワードは必須、文字列、8文字以上
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
            'email.required' => 'メールアドレスを入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは8文字以上で入力してください',
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
