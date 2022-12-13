<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PlayerLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'online_players',
        'player_slots',
        'average_players',
    ];

    public static function index(Request $request)
    {
        if(!($request->token == 'QOk6PeefwIhaufQ3287TMbh9s0hKL5qT')) return response()->json(['message' => 'Unauthorized - wrong token.'], 401);

        $data = array();
        $labels = array();
        $todayStart = Carbon::now()->startOfDay();

        foreach(PlayerLog::orderBy('created_at')->get() as $log)
        {
            array_push($data, $log->online_players);
            $time = Carbon::createFromTimeString($log->created_at);
            array_push($labels, $time<$todayStart?$time->format('d.m. H:i'):$time->format('H:i'));
        }

        return [
            'players' =>[
                'data' => $data,
                'labels' => $labels 
            ],
            'serverCount' => Server::count(),
            'userCount' => User::count()
        ];
    }
}
