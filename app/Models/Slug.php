<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slug extends Model
{
    use HasFactory;

    public function slugCode(int $length = 10): string
    {
        $charSet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $chartSetLen = strlen($charSet);
        $genRanStrn = '';

        for ($i=0; $i < $length; $i++)
        {
            $randomIndex = mt_rand(0, $chartSetLen - 1);
            
            $genRanStrn .= $charSet[$randomIndex];
        }

        return $genRanStrn;
    }
}
