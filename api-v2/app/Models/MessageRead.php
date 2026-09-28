<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Lecture d'un message par une personne. Seuls les messages de l'autre côté de la conversation sont marqués
 * lus : ceux du client par les membres de l'équipe, ceux de l'équipe par le client.
 *
 * @property string $message_id
 * @property string $user_id
 * @property Carbon $read_at
 */
class MessageRead extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'message_id';

    protected $keyType = 'string';

    protected $fillable = [
        'message_id',
        'user_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }
}
