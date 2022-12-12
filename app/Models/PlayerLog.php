<?php

namespace App\Models;

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
        if(!$request->token == 1234) return response()->json(['message' => 'Unauthorized - wrong token.'], 401);

        $data = array();
        $labels = array();

        foreach(PlayerLog::orderBy('created_at')->get() as $log)
        {
            array_push($data, $log->online_players);
            array_push($labels, $log->created_at);
        }

        return [
            'data' => $data,
            'labels' => $labels
        ];
    }
}
