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
        $playSeconds = 0;
        $playHours = 0;
        $players = 0;

        $logs = PlayerLog::orderBy('created_at')->get();
        foreach($logs as $key => $log)
        {
            array_push($data, $log->online_players);
            $time = Carbon::createFromTimeString($log->created_at);
            array_push($labels, $time<$todayStart?$time->format('d.m. H:i'):$time->format('H:i'));

            if(isset($logs[$key+1]))
            {
                $nextLog = $logs[$key+1];
                $timeDifference = Carbon::createFromTimeString($nextLog->created_at)->unix()-$time->unix();
                $playSeconds += ($timeDifference);
                $playHours += ($timeDifference) * $log['online_players'] / 3600;
                $players += $log['online_players'];
            }
        }

        return [
            'players' =>[
                'data' => $data,
                'labels' => $labels 
            ],
            'serverCount' => Server::count(),
            'userCount' => User::count(),
            'averagePlayerCount' => ($logs->count()-1)<=0?"?":round($players/($logs->count()-1), 2),
            'playHoursPerDay' => $playSeconds==0?"?":round(86400/($playSeconds)*$playHours, 2)
        ];
    }
}
