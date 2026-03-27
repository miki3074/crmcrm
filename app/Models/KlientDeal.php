<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KlientDeal extends Model
{
    protected $fillable = [
        'klient_id',
        'contact_person_id',
        'creator_id',
        'name',
        'description',
        'deadline',
        'status',
        'attribute',
        'total_amount'
    ];

    /**
     * СВЯЗЬ С КЛИЕНТОМ (Этого не хватало)
     */
    public function klient(): BelongsTo
    {
        return $this->belongsTo(Klient::class, 'klient_id');
    }

    /**
     * Связь с товарами/позициями сделки
     */
    public function items(): HasMany
    {
        return $this->hasMany(KlientDealItem::class, 'klient_deal_id');
    }

    /**
     * Связь с контактным лицом
     */
    public function contactPerson(): BelongsTo
    {
        return $this->belongsTo(KlientContactPerson::class, 'contact_person_id');
    }

    /**
     * Связь с ответственными пользователями (многие ко многим)
     */
    public function responsibles(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'klient_deal_responsible', 'klient_deal_id', 'user_id');
    }

    /**
     * Связь с задачами (многие ко многим)
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(KlientTask::class, 'klient_deal_task', 'klient_deal_id', 'klient_task_id');
    }

    /**
     * Связь с файлами
     */
    public function files(): HasMany
    {
        return $this->hasMany(KlientDealFile::class, 'klient_deal_id');
    }
}
