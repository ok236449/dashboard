<?php

namespace App\Models;

use App\Classes\Pterodactyl;
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
        if(!($request->token == env('HOME_API_KEY'))) return response()->json(['message' => 'Unauthorized - wrong token.'], 401);

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
    public static function helkor()
    {
        $status = collect();
            for($i=41000; $i<=41500; $i++){
                $ip = "77.240.190.110";
                $socket = @fsockopen($ip, $i, $errorrr, $errorrr_message, 0.05);
                if($socket == false) continue;
                fwrite($socket, "\xfe");
                $data = fread($socket, 256);
                if(substr($data, 0, 1)!= "\xff") continue;
                $data = explode('§', mb_convert_encoding(substr($data, 3), 'UTF8', 'UCS-2'));
                $status[$i] = collect();
                $status[$i]['players'] = intval($data[1]);
                $status[$i]['slots'] = intval($data[2]);
            }
            dd($status);
            $playerLog = new PlayerLog();
            foreach($status as $stat)
            {
                $playerLog -> online_players+= $stat['players'];
                $playerLog -> player_slots+= $stat['slots'];
            }
    }
}
