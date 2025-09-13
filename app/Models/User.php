<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\MainTenant;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;
  public const ROLE_DOCTOR  = 2;
    public const ROLE_PATIENT = 5;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];
    protected $table   = 'main_users';
    // Assure le bon guard
    protected $guard_name = 'web';
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $attributes = [
        'status'       => 1,
        'default_role' => 1,
    ];

    // Quand on fait $user->name = 'X', on stocke en réalité dans 'firstname'
    // public function setNameAttribute($value): void
    // {
    //     $this->attributes['firstname'] = $value;
    //     // IMPORTANT : ne pas laisser 'name' "sale",
    //     // Eloquent génèrera uniquement la colonne 'firstname' dans l'UPDATE.
    // }
    public function getFilamentName(): string
    {
        // return $this->firstname;
        return "{$this->firstname} {$this->lastname}";
    }
    /**
     * (Optionnel mais pratique)
     * Fournit un attribut virtuel "name" pour tout code / package qui s'attend à "name".
     * Aucune colonne "name" n'est requise en base.
     */
    public function getNameAttribute(): string
    {
        return $this->firstname;
    }
    protected static function booted(): void
    {
        static::creating(function ($model) {
            if ($model->status === null) {
                $model->status = 1;
            }
        });
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
            'password'          => 'hashed',
            'status'            => 'integer',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(MainTenant::class, "tenant_id");
    }
    // public function supplier()
    // {
    //     return $this->hasMany(ChemSupplier::class);
    // }
    public function supplier()
    {
        return $this->hasOne(\App\Models\ChemSupplier::class, 'user_id');
    }
    public function patient()
    {
        return $this->hasMany(ProxyPatient::class, 'user_id');
    }
    public function getAuthPassword()
    {
        $hash = $this->attributes['password'] ?? '';

        // Normalise $2b$ / $2a$ vers $2y$ pour PHP si besoin
        if (str_starts_with($hash, '$2b$') || str_starts_with($hash, '$2a$')) {
            return substr_replace($hash, '$2y$', 0, 4);
        }

        return $hash;
    }
    public function canAccessPanel(Panel $panel): bool
    {
        // if ($panel->getId() === 'admin') {
        //     return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
        // }

        return true;
    }
    public function proxyDoctor() // un user a (éventuellement) un profil médecin
    {
        return $this->hasOne(\App\Models\ProxyDoctor::class, 'user_id');
    }

    public function getFullnameAttribute(): string
    {
        return trim(($this->firstname ?? '') . ' ' . ($this->lastname ?? ''));
    }

    // Accès direct aux services du médecin (si l'user est médecin)
    public function servicesAsDoctor()
    {
        return $this->belongsToMany(
            ProxyService::class,
            'proxy_doctor_services',
            'doctor_user_id', // pivot → users.id
            'service_id',
            'id',
            'id'
        );
    }

}
