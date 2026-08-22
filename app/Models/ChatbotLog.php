<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotLog extends Model
{
    /**
     * 🔒 Mass assignable fields.
     *
     * These are the only attributes that may be supplied
     * when creating or updating a ChatbotLog through
     * mass assignment.
     */
    protected $fillable = [
        'user_id',
        'question',
        'answer',
        'agency_id',
        'faq_id',
        'outcome',
        'match_method',
        'score',
        'ip_address',
    ];

    /**
     * 🔗 CHATBOT LOG → AGENCY
     *
     * A chatbot interaction may be associated with an agency.
     *
     * withTrashed() is intentional because an agency may later
     * be soft-deleted while its historical chatbot interactions
     * must remain readable.
     */
    public function agency()
    {
        return $this->belongsTo(
            Agency::class,
            'agency_id'
        )->withTrashed();
    }

    /**
     * 🔗 CHATBOT LOG → FAQ
     *
     * The FAQ that ultimately provided the chatbot answer.
     *
     * The relationship may return null if the FAQ was later
     * deleted or if the interaction did not use an FAQ.
     */
    public function faq()
    {
        return $this->belongsTo(
            Faq::class,
            'faq_id'
        );
    }

    /**
     * 🔗 CHATBOT LOG → USER
     *
     * Every chatbot interaction belongs to an authenticated
     * KNOWURLOCAL account.
     */
    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}