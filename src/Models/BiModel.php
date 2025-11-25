<?php

namespace Luminix\Bi\Models;

use Illuminate\Database\Eloquent\Model;

class BiModel extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection;

    /**
     * Create a new BiModel instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->connection = config('luminix.bi.connection', 'bi_connection');
    }
}
