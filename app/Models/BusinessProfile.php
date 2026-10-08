<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessProfile extends Model
{
    public const STATUS_NEW_INQUIRY = 'new_inquiry';

    public const STATUS_DISCOVERY = 'discovery';

    public const STATUS_PROPOSAL = 'proposal';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'user_id',
        'company_name',
        'company_website',
        'industry',
        'company_size',
        'country',
        'city',
        'job_title',
        'billing_email',
        'services',
        'message',
        'lead_source',
        'engagement_type',
        'team_size',
        'monthly_value',
        'currency',
        'contract_start',
        'contract_end',
        'account_manager',
        'notes',
        'status',
        'status_updated_at',
    ];

    /** Internal admin notes are never shown to the client. */
    protected $hidden = ['notes'];

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'team_size' => 'integer',
            'monthly_value' => 'decimal:2',
            'contract_start' => 'date:Y-m-d',
            'contract_end' => 'date:Y-m-d',
            'status_updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
