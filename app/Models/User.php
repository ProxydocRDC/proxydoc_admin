<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use App\Models\MainTenant;
use App\Models\MainUserAddress;
use Filament\Models\Contracts\HasName;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Concerns\HasS3MediaUrls;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles,HasS3MediaUrls;
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
            // Génération automatique des codes promo et parrainage (6 caractères, lettres+chiffres, majuscules)
            if (empty($model->code_promo)) {
                $model->code_promo = static::generateUniqueCode('code_promo', $model->code_parrainage ?? null);
            }
            if (empty($model->code_parrainage)) {
                $model->code_parrainage = static::generateUniqueCode('code_parrainage', $model->code_promo);
            }
        });
    }

    /**
     * Génère un code unique de 6 caractères (lettres et chiffres, majuscules).
     */
    public static function generateUniqueCode(string $column, ?string $exclude = null): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $maxAttempts = 1000;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            if ($code === $exclude) {
                continue;
            }
            $exists = static::where($column, $code)->exists();
            if (!$exists) {
                return $code;
            }
        }

        throw new \RuntimeException("Impossible de générer un code unique pour {$column} après {$maxAttempts} tentatives.");
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'status'       => 'integer',
            'birth_date'   => 'date',
            'last_activity' => 'datetime',
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

    /** Fiche patient "moi-même" (relation self) */
    public function selfPatient()
    {
        return $this->hasOne(ProxyPatient::class, 'user_id')->where('relation', 'self');
    }

    /** Vérifie si l'utilisateur a une fiche patient (relation self) */
    public function hasPatientRecord(): bool
    {
        return $this->selfPatient()->exists();
    }
    public function customerAdresse()
    {
        return $this->hasMany(MainUserAddress::class, 'user_id');
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
    public function shipments()
    {
        return $this->hasMany(\App\Models\ChemShipment::class, 'delivery_person_id');
    }
    public function getFullnameAttribute(): string
    {
        return trim(($this->firstname ?? '') . ' ' . ($this->lastname ?? ''));
    }

    /**
     * Utilisateurs parrainés par cet utilisateur (leur code_parrainage = notre code_promo).
     */
    public function parraines()
    {
        return $this->hasMany(User::class, 'code_parrainage', 'code_promo');
    }

    /**
     * Scope : utilisateurs dont le code_parrainage correspond au code_promo du parrain donné.
     */
    public function scopeParrainesDe(Builder $query, User $parrain): Builder
    {
        return $query->where('code_parrainage', $parrain->code_promo);
    }

    /**
     * Régénère le code promo pour un utilisateur existant.
     */
    public function regenerateCodePromo(): string
    {
        $this->code_promo = static::generateUniqueCode('code_promo', $this->code_parrainage);
        $this->saveQuietly();
        return $this->code_promo;
    }

    /**
     * Régénère le code parrainage pour un utilisateur existant.
     */
    public function regenerateCodeParrainage(): string
    {
        $this->code_parrainage = static::generateUniqueCode('code_parrainage', $this->code_promo);
        $this->saveQuietly();
        return $this->code_parrainage;
    }

    /**
     * Vérifie si l'utilisateur a terminé le processus de création de compte.
     * Basé sur status et OTP : status 5 = "en cours de création, doit valider l'OTP".
     * Les autres statuts (1, 2, 3, 4) indiquent que l'OTP a été validé ou que le processus est plus avancé.
     */
    public function hasCompletedRegistration(): bool
    {
        return (int) $this->status !== 5;
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
public function getImageUrlAttribute(): ?string
    {
        return $this->mediaUrl('profiles');       // <img src="{{ $category->image_url }}">
    }

    public function getImagesUrlsAttribute(): array
    {
        return $this->mediaUrls('profile');     // foreach ($model->images_urls as $url) ...
    }
    public function getProfileUrlAttribute(): ?string
{
    $path = $this->profile; // colonne qui contient le chemin S3 (string)
    if (empty($path)) {
        return null;
    }

    try {
        return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(10));
    } catch (\Throwable $e) {
        return null;
    }
}
}
