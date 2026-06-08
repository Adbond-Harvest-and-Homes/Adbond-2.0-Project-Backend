<?php

namespace app\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use app\Enums\RefererCodePrefix;
use app\Utilities;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier() {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims() {
        return [];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static $userType = "app\Models\User";

    public function getNameAttribute()
    {
        $fullname = '';
        if($this->firstname && !empty($this->firstname)) $fullname .= $this->firstname.' ';
        if($this->lastname && !empty($this->lastname)) $fullname .= $this->lastname.' ';
        return $fullname;
    }

    public function staffType()
    {
        return $this->belongsTo("app\Models\StaffType");
    }

    public function role()
    {
        return $this->belongsTo("app\Models\Role");
    }

    public function photo()
    {
        return $this->belongsTo("app\Models\File");
    }

    public function registerer()
    {
        return $this->belongsTo("app\Models\User", "registered_by", "id");
    }

    public function staffReferrals()
    {
        return $this->hasMany(User::class, "registered_by", "id");
    }

    public function clients(): MorphMany
    {
        return $this->morphMany(Client::class, 'referer');
    }

    /**
     * Get all commission earnings for the user
     */
    public function commissionEarnings()
    {
        return $this->hasMany(StaffCommissionEarning::class);
    }

    /**
     * Get all commission redemptions for the user
     */
    public function commissionRedemptions()
    {
        return $this->hasMany(StaffCommissionRedemption::class);
    }

    /**
     * Get all commission transactions for the user
     */
    public function commissionTransactions()
    {
        return $this->hasMany(StaffCommissionTransaction::class);
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'user');
    }

    // Posts liked by the user
    public function likedPosts()
    {
        return Post::whereHas('reactions', function ($query) {
            $query->where('user_id', $this->id)
                ->where('user_type', self::$userType)
                ->where('reaction', true)
                ->where('entity_type', Post::class);
        });
    }

    public function likedPostIds()
    {
        $ids = [];
        // dd($this->likedPosts()->pluck("posts.id")->toArray());
        if($this->likedPosts()->count() > 0) {
            foreach($this->likedPosts() as $post) $ids[] = $post->id;
        }
        return $ids;
    }

    // Comments liked by the user
    public function likedComments()
    {
        return Comment::whereHas('reactions', function ($query) {
            $query->where('user_id', $this->id)
                ->where('user_type', self::$userType)
                ->where('reaction', true)
                ->where('entity_type', Comment::class);
        });
    }

    public function likedCommentIds()
    {
        $ids = [];
        if($this->likedComments()->count() > 0) {
            foreach($this->likedComments() as $comment) $ids[] = $comment->id;
        }
        return $ids;
    }

    // Posts disliked by the user
    public function dislikedPosts()
    {
        return Post::whereHas('reactions', function ($query) {
            $query->where('user_id', $this->id)
                ->where('user_type', self::$userType)
                ->where('reaction', 0)
                ->where('entity_type', Post::$type);
        });
    }

    public function dislikedPostIds()
    {
        $ids = [];
        if($this->dislikedPosts()->count() > 0) {
            foreach($this->dislikedPosts() as $post) $ids[] = $post->id;
        }
        return $ids;
    }

    // Comments disliked by the user
    public function dislikedComments()
    {
        return Comment::whereHas('reactions', function ($query) {
            $query->where('user_id', $this->id)
                ->where('user_type', self::$userType)
                ->where('reaction', false)
                ->where('entity_type', Comment::class);
        });
    }

    public function dislikedCommentIds()
    {
        $ids = [];
        if($this->dislikedComments()->count() > 0) {
            foreach($this->dislikedComments() as $comment) $ids[] = $comment->id;
        }
        return $ids;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $prefix = RefererCodePrefix::USER->value;
            
            if (empty($user->referer_code)) {
                do {
                    $suffix = Utilities::generateRandomString(8);
                    $code = $prefix . $suffix;
                    $exists = self::where('referer_code', $code)->exists();
                } while ($exists);
                
                $user->referer_code = $code;
                $user->staff_referer_code = $suffix;
            } else {
                if (str_starts_with($user->referer_code, $prefix)) {
                    $suffix = substr($user->referer_code, strlen($prefix));
                } else {
                    $suffix = trim($user->referer_code);
                    $user->referer_code = $prefix . $suffix;
                }
                
                if (empty($user->staff_referer_code)) {
                    $user->staff_referer_code = $suffix;
                }
            }
        });
    }
}
