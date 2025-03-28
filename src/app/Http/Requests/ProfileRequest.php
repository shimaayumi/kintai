<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    /**
     * ユーザーがこのリクエストを実行する権限があるかを判断します。
     *
     * @return bool
     */
    public function authorize()
    {
        // プロフィールフォームのリクエストは、全てのユーザーに許可
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
            'profile_image' => 'nullable|mimes:jpeg,png|max:2048', // プロフィール画像はオプション、jpegまたはpng、最大2MB
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
            'profile_image.mimes' => 'プロフィール画像はJPEGまたはPNG形式である必要があります。',
            'profile_image.max' => 'プロフィール画像は2MB以下でなければなりません。',
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
