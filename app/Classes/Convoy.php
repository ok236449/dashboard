<?php

namespace App\Classes;

use Exception;
use Illuminate\Support\Facades\Http;

class Convoy{

    public static function client()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . env('CONVOY_BEARER_TOKEN'),
            'Content-type' => 'application/json'
        ])->baseUrl(env('CONVOY_URL') . '/api/application/');
    }

    public static function fetchServer($uuid){
        try {
            $response = self::client()->get('servers/' . $uuid);
        } catch (Exception $e) {
            //throw new Exception($e->getMessage());
        }
        return isset($response)?$response->json():null;
    }

    public static function fetchNodes(){
        try {
            $response = self::client()->get('nodes');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response->json();
    }

    public static function fetchNode($id){
        try {
            $response = self::client()->get('nodes/' . $id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response->json();
    }
    
    public static function suspendServer($uuid){
        try {
            $response = self::client()->post('servers/' . $uuid . '/settings/suspend');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response->status();
    }

    public static function unsuspendServer($uuid){
        try {
            $response = self::client()->post('servers/' . $uuid . '/settings/unsuspend');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response->status();
    }

}