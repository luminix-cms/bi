<?php

namespace Luminix\Bi\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class NoteModel extends Model
{
    protected $table = 'notes';

    public function noteable()
    {
        return $this->morphTo();
    }
}
