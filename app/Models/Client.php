<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

   

    protected $fillable = [
    'type', 'organization_name', 'name', 'city', 'address',
    'phone', 'email', 'notes', 'company_id', 'project_id', 'responsible_id', 'created_by',
];


    public function company(){ return $this->belongsTo(Company::class); }
    public function interactions(){ return $this->hasMany(Interaction::class)->latest('interaction_date'); }
    public function deals(){ return $this->hasMany(Deal::class)->latest(); }


 

    // 🔗 Клиент может быть связан с проектом
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

     public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function creator()
{
    return $this->belongsTo(User::class, 'created_by');
}


}
