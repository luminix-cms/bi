<?php

namespace Luminix\Bi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiWidgetData extends Model
{
    protected $table = 'bi_widgets_data';
    protected $primaryKey = 'ulid';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'widget_ulid',
        'data',
        'source_type',
        'source_details',
        'last_updated'
    ];

    protected $casts = [
        'data' => 'array',
        'source_details' => 'array'
    ];
    
    public function widget(): BelongsTo
    {
        return $this->belongsTo(BiWidget::class);
    }
}
