<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'started_at' => 'required|date_format:H:i',
            'ended_at' => 'required|date_format:H:i|after:started_at',
            'note' => 'required|string',
            'breaks' => 'array',
            'breaks.*.break_started_at' => 'required|date_format:H:i',
            'breaks.*.break_ended_at' => 'required|date_format:H:i',
        ];
    }

    public function messages()
    {
        return [
            'started_at.required' => '出勤時間は必須です',
            'ended_at.required' => '退勤時間は必須です',
            'ended_at.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'note.required' => '備考を記入してください',
            'breaks.*.break_started_at.required' => '休憩開始時間は必須です',
            'breaks.*.break_ended_at.required' => '休憩終了時間は必須です',
         
        ];
    }


    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $breaks = $this->input('breaks', []);
            $startedAt = $this->input('started_at');
            $endedAt = $this->input('ended_at');

            foreach ($breaks as $index => $break) {
                $breakStartedAt = $break['break_started_at'] ?? null;
                $breakEndedAt = $break['break_ended_at'] ?? null;

                // 1. 休憩終了時間が開始時間より前
                if ($breakStartedAt && $breakEndedAt && strtotime($breakEndedAt) <= strtotime($breakStartedAt)) {
                    $validator->errors()->add(
                        "breaks.$index.break_ended_at",
                        '休憩開始時間もしくは休憩終了時間が不適切な値です'
                    );
                    continue; // このチェックに引っかかったら次の休憩へ
                }

                // 2. 勤務時間外の休憩チェック（重複防止のため、ここだけで判定）
                if ($startedAt && $endedAt) {
                    $workStart = strtotime($startedAt);
                    $workEnd = strtotime($endedAt);

                    if ($breakStartedAt) {
                        $breakStart = strtotime($breakStartedAt);
                        if ($breakStart < $workStart || $breakStart > $workEnd) {
                            $validator->errors()->add(
                                "breaks.$index.break_started_at",
                                '休憩時間が勤務時間外です。'
                            );
                            continue;
                        }
                    }

                    if ($breakEndedAt) {
                        $breakEnd = strtotime($breakEndedAt);
                        if ($breakEnd < $workStart || $breakEnd > $workEnd) {
                            $validator->errors()->add(
                                "breaks.$index.break_ended_at",
                                '休憩時間が勤務時間外です。'
                            );
                        }
                    }
                }
            }
        });
    }
}