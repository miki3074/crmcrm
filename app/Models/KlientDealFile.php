<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KlientDealFile extends Model
{
    use HasFactory;

    protected $table = 'klient_deal_files';

    protected $fillable = [
        'klient_deal_id',
        'user_id',
        'original_name',
        'file_path',
        'file_size'
    ];

    /**
     * Связь с самой сделкой
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(KlientDeal::class, 'klient_deal_id');
    }

    /**
     * Связь с пользователем, который загрузил файл
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
