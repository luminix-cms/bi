<?php

namespace Luminix\Bi\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';

    public function company()
    {
        return $this->belongsTo(CompanyModel::class, 'company_id');
    }

    public function orders()
    {
        return $this->hasMany(OrderModel::class, 'customer_id');
    }

    public function tags()
    {
        return $this->belongsToMany(TagModel::class, 'customer_tag', 'customer_id', 'tag_id');
    }

    public function notes()
    {
        return $this->morphMany(NoteModel::class, 'noteable');
    }
}
