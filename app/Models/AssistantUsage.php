<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

// Daily counters of how the assistant answered. No questions, no names, no addresses.
class AssistantUsage extends Model
{
    protected $table = 'assistant_usage';

    public $timestamps = false;

    protected $fillable = ['day', 'mode', 'total'];

    // Adds one to today's counter. A problem here NEVER stops an answer.
    public static function hit(string $mode): void
    {
        try {
            $day = now(config('shop.timezone'))->toDateString();
            $row = DB::table('assistant_usage')->where('day', $day)->where('mode', $mode);

            if ((clone $row)->increment('total') === 0) {
                try {
                    DB::table('assistant_usage')->insert(['day' => $day, 'mode' => $mode, 'total' => 1]);
                } catch (UniqueConstraintViolationException) {
                    $row->increment('total');   // two visitors at the same moment
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}