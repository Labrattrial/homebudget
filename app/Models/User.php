<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Category;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_picture',
        'currency',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['profile_picture_url', 'currency_symbol'];

    // Relationship definition with correct return types
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function getProfilePictureUrlAttribute()
    {
        if (!$this->profile_picture) {
            return asset('images/default-profile.png');
        }

        // Convert binary data to base64
        $base64 = base64_encode($this->profile_picture);
        
        // Detect MIME type from binary data
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($this->profile_picture);

        // Default to image/jpeg if detection fails or is not an image type
        if (!$mime || !str_starts_with($mime, 'image/')) {
            $mime = 'image/jpeg'; // Fallback
        }

        return "data:{$mime};base64,{$base64}";
    }

    public function getProfilePicturePathAttribute()
    {
        if ($this->profile_picture) {
            return 'public/' . $this->profile_picture;
        }
        return 'public/defaults/default-profile.png';
    }

    public function getCurrencySymbolAttribute()
    {
        return \App\Helpers\CurrencyHelper::getCurrencySymbol($this->currency);
    }
}
