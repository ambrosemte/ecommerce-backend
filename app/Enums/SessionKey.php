<?php

namespace App\Enums;

enum SessionKey: string
{
    case RecentlyViewed = 'guest_%s_recently_viewed';
    case Wishlist = 'guest_%s_wishlist';
    case Cart = 'guest_%s_cart';
    case FirebaseToken = 'firebase_tokens';

    public function format(string $guestId): string
    {
        return sprintf($this->value, $guestId);
    }
}
