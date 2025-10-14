@component('mail::message')
# 🏠 New Property Suggestion for You!

A new property has just been listed that might interest you:

**{{ $title }}**

📍 **Address:** {{ $address ?? 'N/A' }}  
💰 **Price:** ₱{{ number_format($price ?? 0, 2) }}  
📝 **Description:** {{ $description ?? 'No description provided.' }}

@component('mail::button', ['url' => $propertyUrl])
View Property
@endcomponent

Thanks,  
**Commercial Hub Team**
@endcomponent