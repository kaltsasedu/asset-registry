<?php
namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class AssetCustodian extends Model
{
    use LogsBusinessActivity;

    protected $fillable = ['name', 'email', 'phone', 'department_id', 'user_id', 'active'];

    protected static function booted(): void
    {
        static::saving(function (self $custodian): void {
            if ($custodian->isDirty('user_id') && auth()->user() && ! auth()->user()->hasRole('Διαχειριστής') && ! auth()->user()->can('users.manage_data_scope')) {
                throw ValidationException::withMessages([
                    'user_id' => 'Η σύνδεση υπευθύνου με χρήστη αλλάζει την περιοχή πρόσβασης και επιτρέπεται μόνο σε εξουσιοδοτημένο διαχειριστή.',
                ]);
            }
        });
    }

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
