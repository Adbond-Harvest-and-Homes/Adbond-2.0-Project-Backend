<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use app\Models\Post;
use app\Models\Comment;

use app\Enums\RefererCodePrefix;

class Client extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'mysql';

    public static $userType = "app\Models\Client";

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

    public function getFullNameAttribute()
    {
        $fullname = '';
        if($this->title && !empty($this->title)) $fullname .= $this->title.' ';
        if($this->firstname && !empty($this->firstname)) $fullname .= $this->firstname.' ';
        if($this->lastname && !empty($this->lastname)) $fullname .= $this->lastname.' ';
        if($this->othername && !empty($this->othername)) $fullname .= $this->othername.' ';
        return $fullname;
    }

    public function getNameAttribute()
    {
        $name = '';
        if($this->firstname && !empty($this->firstname)) $name .= $this->firstname.' ';
        if($this->lastname && !empty($this->lastname)) $name .= $this->lastname.' ';
        return $name;
    }

    public function photo()
    {
        return $this->belongsTo("app\Models\File");
    }

    public function clientIdentification()
    {
        return $this->belongsTo(ClientIdentification::class);
    }

    // public function referer()
    // {
    //     return $this->morphOne();
    // }
    /**
     * Get the referer model (User or Client).
     */
    public function referer(): MorphTo
    {
        return $this->morphTo();
    }

    public function referrals(): MorphMany
    {
        return $this->morphMany(Client::class, 'referer');
    }

    public function nextOfKins()
    {
        return  $this->hasOne(ClientNextOfKin::class, "client_id", "id");
        // if(!$nextOfKins || $nextOfKins->count() == 0) return null;
        // return $nextOfKins;
    }

    public function assets()
    {
        return $this->hasMany(ClientPackage::class, "client_id", "id");
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function siteTourSchedules()
    {
        return $this->hasManyThrough(SiteTourSchedule::class, SiteTourBooking::class, "client_id", "id", "id", "site_tour_schedule_id");
    }

    public function siteTourBookings()
    {
        return $this->hasMany(SiteTourBooking::class);
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'user');
    }

    // Posts liked by the user
    // public function likedPosts()
    // {
    //     return $this->morphedByMany(Post::class, 'entity', 'reactions')
    //                 ->where('reaction', true);
    // }
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
        if($this->likedPosts()->count() > 0) {
            foreach($this->likedPosts() as $post) $ids[] = $post->id;
        }
        return $ids;
    }

    // Comments liked by the user
    // public function likedComments()
    // {
    //     return $this->morphedByMany(Comment::class, 'entity', 'reactions')
    //                 ->where('reaction', true);
    // }
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
    // public function dislikedPosts()
    // {
    //     return $this->morphedByMany(Post::class, 'entity', 'reactions')
    //                 ->where('reaction', false);
    // }
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
    // public function dislikedComments()
    // {
    //     return $this->morphedByMany(Comment::class, 'entity', 'reactions')
    //                 ->where('reaction', false);
    // }

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

    public function commissionEarnings()
    {
        return $this->hasMany(ClientCommissionEarning::class);
    }

    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function (Client $client) {
            if($client->wallet) $client->wallet->delete();

            if($client->nextOfKins) $client->nextOfKins->delete();

            if($client->clientIdentification) $client->clientIdentification->delete();

            if($client->photo) $client->photo->delete();

            if($client->siteTourBookings->count() > 0) {
                foreach($client->siteTourBookings as $booking) $booking->delete();
            }

            if($client->reactions->count() > 0) {
                foreach($client->reactions as $reaction) $reaction->delete();
            }

            if($client->commissionEarnings->count() > 0) {
                foreach($client->commissionEarnings as $earning) $earning->delete();
            }

            if($client->referrals->count() > 0) {
                foreach($client->referrals as $referral) {
                    $referral->referer_id = null;
                    $referral->referer_type = null;
                    $referral->update();
                }
            }

        });
    }
}
