<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'bio',
        'profession',
        'location',
        'interests',
        'hobbies',
        'profile_photo',
        'cover_photo',
        'phone',
        'locale',
        'timezone',
    ];

    public function profilePosts(): HasMany
    {
        return $this->hasMany(Post::class)->latest();
    }

    public function sentConversations(): HasMany { return $this->hasMany(Conversation::class, 'user_one_id'); }
    public function receivedConversations(): HasMany { return $this->hasMany(Conversation::class, 'user_two_id'); }
    public function messages(): HasMany { return $this->hasMany(Message::class); }

    public function unreadMessagesCount(): int
    {
        return Message::query()
            ->whereNull('read_at')
            ->where('user_id', '!=', $this->id)
            ->whereHas('conversation', fn ($query) => $query->where('user_one_id', $this->id)->orWhere('user_two_id', $this->id))
            ->count();
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo && filter_var($this->profile_photo, FILTER_VALIDATE_URL)) {
            return $this->profile_photo;
        }

        return $this->profile_photo
            ? asset('storage/'.$this->profile_photo)
            : 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=2b7de9&color=fff';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone_verified_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'two_factor_recovery_codes' => 'array',
            'locked_until' => 'datetime',
            'deactivated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function deviceSessions(): HasMany
    {
        return $this->hasMany(DeviceSession::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function profilePhotos(): HasMany { return $this->hasMany(ProfilePhoto::class); }
    public function coverPhotos(): HasMany { return $this->hasMany(CoverPhoto::class); }
    public function connections(): HasMany { return $this->hasMany(SocialConnection::class); }
    public function friendships(): HasMany { return $this->hasMany(Friendship::class, 'user_one_id')->orWhere('user_two_id', $this->id); }
    public function followers(): HasMany { return $this->hasMany(Follower::class, 'following_id'); }
    public function following(): HasMany { return $this->hasMany(Follower::class, 'follower_id'); }

    public function isAvailable(): bool
    {
        return in_array($this->status, ['ACTIVE', 'PENDING_VERIFICATION'], true)
            && ! $this->deactivated_at
            && ! $this->deleted_at;
    }
}
