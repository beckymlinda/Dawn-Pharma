<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'ip_address',
        'user_agent',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Log user activity
     */
    public static function logActivity($action, $description = null, $subject = null)
    {
        $log = [
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        if ($subject) {
            $log['subject_type'] = get_class($subject);
            $log['subject_id'] = $subject->id;
        }

        return static::create($log);
    }

    /**
     * Get action icon
     */
    public function getActionIconAttribute()
    {
        $icons = [
            'created' => 'bi-plus-circle',
            'updated' => 'bi-pencil',
            'deleted' => 'bi-trash',
            'login' => 'bi-box-arrow-in-right',
            'logout' => 'bi-box-arrow-right',
            'viewed' => 'bi-eye',
            'exported' => 'bi-download',
            'imported' => 'bi-upload',
            'approved' => 'bi-check-circle',
            'rejected' => 'bi-x-circle',
            'sold' => 'bi-cart-check',
            'purchased' => 'bi-bag-plus',
            'adjusted' => 'bi-arrow-left-right',
            'opened' => 'bi-door-open',
            'closed' => 'bi-door-closed',
        ];

        return $icons[$this->action] ?? 'bi-activity';
    }

    /**
     * Get action color
     */
    public function getActionColorAttribute()
    {
        $colors = [
            'created' => 'success',
            'updated' => 'primary',
            'deleted' => 'danger',
            'login' => 'info',
            'logout' => 'secondary',
            'viewed' => 'info',
            'exported' => 'warning',
            'imported' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'sold' => 'success',
            'purchased' => 'primary',
            'adjusted' => 'warning',
            'opened' => 'info',
            'closed' => 'secondary',
        ];

        return $colors[$this->action] ?? 'secondary';
    }

    /**
     * Get formatted time ago
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Scope for filtering by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by action
     */
    public function scopeForAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
