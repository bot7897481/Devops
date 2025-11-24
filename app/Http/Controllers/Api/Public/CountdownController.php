<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CountdownController extends Controller
{
    public function getCountdown()
    {
        $applicationOpenDate = config('app.application_open_date');

        return response()->json([
            'application_open_date' => $applicationOpenDate,
            'is_open' => now()->gte($applicationOpenDate),
            'time_remaining' => now()->diffInSeconds($applicationOpenDate, false),
        ]);
    }
}
