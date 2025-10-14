<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_name',
        'office_address',
        'tell_number',
        'fax_number',
    ];

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    // Count borrowed items by department
    public function borrowedItemsCount()
    {
        return $this->accounts()->withCount('borrows')->get();
    }
}
