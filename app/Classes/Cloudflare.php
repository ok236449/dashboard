<?php

namespace App\Classes;

use Exception;
use Illuminate\Support\Facades\Http;

class Cloudflare{

    public static function client()
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . env('CLOUDFLARE_BEARER_TOKEN'),
            'Content-type' => 'application/json'
        ])->baseUrl('https://api.cloudflare.com/client/v4');
    }
    public static function getRecords($zone_id){
        try {
            $response = self::client()->get('/zones/' . $zone_id . '/dns_records');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response->json();
    }
    public static function createRecord($zone_id, $type, $name, $content, $port = 0){
        try {
            if($type=="A"||$type=="CNAME") $response = self::client()->post('/zones/' . $zone_id . '/dns_records', [
                'content' => $content,
                'name' => $name,
                'proxied' => true,
                'type' => $type,
                'comment' => 'Created with API'
            ]);
            else if($type=="SRV") $response = self::client()->post('/zones/' . $zone_id . '/dns_records', [
                'data' => [
                    'name' => $name,
                    'port' => $port,
                    'priority' => 10,
                    'proto' => "_tcp",
                    'service' => "_minecraft",
                    'target' => $content,
                    'weight' => 10
                ],
                'type' => $type,
                'comment' => 'Created with API'
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response->json();
    }
    public static function patchRecord($zone_id, $record_id, $type, $name, $content, $port = 0){
        try {
            if($type=="A"||$type=="CNAME") $response = self::client()->patch('/zones/' . $zone_id . '/dns_records/' . $record_id, [
                'content' => $content,
                'name' => $name,
                //'proxied' => false,
                'type' => $type,
                //'comment' => 'Created with API'
            ]);
            else if($type=="SRV") $response = self::client()->patch('/zones/' . $zone_id . '/dns_records/' . $record_id, [
                'data' => [
                    'name' => $name,
                    'port' => $port,
                    'priority' => 10,
                    'proto' => "_tcp",
                    'service' => "_minecraft",
                    'target' => $content,
                    'weight' => 10
                ],
                'type' => $type,
                //'comment' => 'Created with API'
            ]);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response->json();
    }
    public static function deleteRecord($zone_id, $record_id){
        try {
            $response = self::client()->delete('/zones/' . $zone_id . '/dns_records/' . $record_id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response->json();
    }
    public static function createCustomHostname($name, $zone_id = "06caf79d8d0a268e8aafe0c21c4cb67d"){
        try {
            $response = self::client()->withHeaders(['X-Auth-Email' => 'ok236449@email.cz'])->post('/zones/' . $zone_id . '/custom_hostnames', [
                "hostname" => $name,
                'ssl' => [
                    'method' => 'http',
                    'type' => 'dv'
                ]
            ]);
            //dd($response);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response->json();
    }
    public static function deleteCustomHostname($id, $zone_id = "06caf79d8d0a268e8aafe0c21c4cb67d"){
        try {
            $response = self::client()->delete('/zones/' . $zone_id . '/custom_hostnames/' . $id, []);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response->json();
    }
}
