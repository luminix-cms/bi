<?php

namespace Luminix\Bi\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TagModel extends Model
{
    protected $table = 'tags';

    public function customers()
    {
        return $this->morphedByMany(CustomerModel::class, 'taggable', 'taggables', 'tag_id', 'taggable_id');
    }
}
