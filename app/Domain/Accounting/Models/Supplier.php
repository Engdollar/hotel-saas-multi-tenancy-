<?php

namespace App\Domain\Accounting\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $table = 'accounting_suppliers';

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'tax_identifier',
        'status',
        'notes',
    ];

    public function bills()
    {
        return $this->hasMany(SupplierBill::class);
    }
}