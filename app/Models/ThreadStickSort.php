<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThreadStickSort extends Model
{
    protected $table = 'thread_stick_sort';

    const THREAD_STICK_COUNT_LIMIT = 20;
}
