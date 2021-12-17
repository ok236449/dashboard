<?php

namespace App\Models;

use App\Classes\Pterodactyl;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Node extends Model
{
    use HasFactory;

    public $incrementing = false;

    public $guarded = [];

    public static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub

        static::deleting(function (Node $node) {
            $node->products()->detach();
        });
    }


    /**
     * @throws Exception
     */
    public static function syncNodes()
    {
        Location::syncLocations();
        $nodes = Pterodactyl::getNodes();

        //map response
        $nodes = array_map(function ($node) {
            return array(
                'id'          => $node['attributes']['id'],
                'location_id' => $node['attributes']['location_id'],
                'name'        => $node['attributes']['name'],
                'description' => $node['attributes']['description'],
            );
        }, $nodes);

        //update or create
        foreach ($nodes as $node) {
            self::query()->updateOrCreate(
                [
                    'id' => $node['id']
                ],
                [
                    'name'        => $node['name'],
                    'description' => $node['description'],
                    'location_id' => $node['location_id'],
                    'disabled'    => false
                ]);
        }

        self::removeDeletedNodes($nodes);
    }

    /**
     * @description remove nodes that have been deleted on pterodactyl
     * @param array $nodes
     */
    private static function removeDeletedNodes(array $nodes): void
    {
        $ids = array_map(function ($data) {
            return $data['id'];
        }, $nodes);

        self::all()->each(function (Node $node) use ($ids) {
            if (!in_array($node->id, $ids)) $node->delete();
        });
    }

    /**
     * @return BelongsTo
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(Product::class);
    }
}
