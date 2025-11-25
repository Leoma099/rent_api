<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PropertySuggestionMail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    public $property;

    public function __construct($property)
    {
        $this->property = $property;
    }

    public function build()
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:8080'), '/');

        return $this->subject('🏠 New Property Suggestion for You!')
            ->markdown('emails.property_suggestion', [
                'title' => $this->property->title ?? 'New Property',
                'address' => $this->property->address ?? 'N/A',
                'price' => $this->property->price ?? 0,
                'description' => $this->property->description ?? 'No description available',
                // ✅ FIXED: added the missing slash before the ID
                'propertyUrl' => $frontendUrl . '/commercialhub/properties/' . $this->property->id,
            ]);
    }
}