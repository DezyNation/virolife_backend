<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Bank;
use App\Models\Group;
use App\Models\Campaign;
use App\Models\Subscription;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'account_number',
        'primary_transaction_id',
        'secondary_transaction_id',
        'ad_points',
        'commission',
        'stars',
        'health_points',
        'code',
        'phone_number',
        'otp_generatd_at',
        'micr',
        'ifsc',
        'on_hold',
        'otp',
        'address',
        'upi_id',
        'id_type',
        'bank_name',
        'email',
        'gender',
        'password',
        'parent_id',
        'active',
        'wallet',
        'profile',
        'provider',
        'acive',
        'provider_id',
        'primary_activated',
        'secondary_actived',
        'round',
        'group_collection',
        'is_current_round_complete'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'otp',
        'otp_generatd_at',
        'remember_token',
        // 'created_at',
        // 'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get all of the banks for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function banks(): HasMany
    {
        return $this->hasMany(Bank::class);
    }

    public function donations(): MorphMany
    {
        return $this->morphMany(Donation::class, 'donatable');
    }

    /**
     * Get all of the campaigns for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Get the group associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function group(): HasOne
    {
        return $this->hasOne(Group::class, 'parent_id');
    }

    /**
     * The groupMembership that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groupMembership(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'secondary_groups', 'parent_id');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'secondary_groups', 'user_id');
    }

    /**
     * Get the subscription associated with the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }
}
