<?php

namespace App\Http\Controllers;

/**
 * Favourites - the shop's second saved list.
 *
 * The same thing the wishlist is, kept apart from it on purpose: a shopper
 * sorting a long wishlist into the few they actually mean to buy needs somewhere
 * to put them that is not the list they are sorting. Two lists, one behaviour -
 * everything but the cookie, the page and the star is inherited.
 *
 * No DB table and no write endpoints, unlike WishlistController. The wishlist
 * has both and neither is ever reached from the storefront: the cookie is the
 * list, so a `favourites` table would be a table nothing writes to and every
 * count read off it would be structurally zero. Adding one here would have
 * shipped the wishlist's dead half a second time.
 *
 * Saving still takes an account, exactly as the wishlist does - the gate is
 * kkRequireLogin on the button, not a route, because there is no write route.
 * If either list ever needs to follow a shopper between devices, that is a
 * change both should get at once, in SavedListController.
 */
class FavouriteController extends SavedListController
{
    protected function cookieName(): string
    {
        return 'kk_favourites';
    }

    protected function view(): string
    {
        return 'favourites.index';
    }
}
