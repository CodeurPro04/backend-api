<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRequestReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_request_id',
        'agent_id',
        'report_type',
        'content',
        'summary',
        'client_feedback',
        'next_step',
        'sale_price',
        'closure_note',
        'concluded_at',
    ];

    protected $casts = [
        'concluded_at' => 'datetime',
    ];

    public function clientRequest()
    {
        return $this->belongsTo(ClientRequest::class, 'client_request_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
