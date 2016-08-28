<?php

use Illuminate\Support\Collection;

require_once 'vendor/autoload.php';

$guests = require 'guests.php';

$find = isset($_GET['find']) ? $_GET['find'] : 'booking_number';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'booking_number';

// Following Code block is messy!
// Just for demonstration purposes only

echo 'You may update what to pull out and sort by. <br>';
echo 'You can do this by setting the "find" and "sort" variables in the URL. <br>';
echo 'EX. http://sourcetoadchallenge.dev/?find=first_name&sort=last_name <br>';

echo '<br><br>';

echo 'Found Guest Information <br>';
echo 'Guest\'s ' . $find . '<br>';
echo '<pre>';
print_r(findGuestsInfo($guests, $find)->all());
echo '</pre>';

echo '<br><br>';

echo 'Sorted Guest Information <br>';
echo 'Sorted by Guest\'s ' . $sort . '<br>';
echo '<pre>';
print_r(sortGuestsInfo($guests, $find)->all());
echo '</pre>';

// Ok, back to clean(er) code

/**
 * The single function to pull out a collection of guest information given a key.
 * You may also optionally add a $guestId to look up a specific guest's information.
 *
 * @param  Array    $guests       An array of guest information
 * @param  string   $key          The field you want to punch out
 * @param  int|null $guestId      Optional $guestId to find guest specific information
 *
 * @return Collection             Returns the desired information or an error message if the key is unable to be found
 */
function findGuestsInfo(Array $guests, string $key, int $guestId = null)
{
    $guests = scopeGuests($guests, $guestId);

    $results = searchCollection($guests, $key);
    if ($results->count()) return $results;

    $booking = $guests->flatMap(function ($guest) {
        return isset($guest['guest_booking']) ? $guest['guest_booking'] : null;
    });
    $results = searchCollection($booking, $key);
    if ($results->count()) return $results;

    $accounts = $guests->flatMap(function ($guest) {
        return isset($guest['guest_account']) ? $guest['guest_account'] : null;
    });
    $results = searchCollection($accounts, $key);
    if ($results->count()) return $results;

    return collect(['status' => 404, 'message' => 'unable to find guest information']);
}

/**
 * The single function to order the guests according to any key provided.
 *
 * I was unclear on one part of the challenge - "i.e. sort by last_name AND/OR sort by account_id".
 * This function will sort by last_name OR account_id, but cannot sort by last_name THEN account_id.
 * In order to accomplish a multi-key sort the function would have to accept an Array as the key,
 * then loop over the array of keys and use a PHP function like array_multisort().
 *
 * @param  Array    $guests       An array of guest information
 * @param  string   $key          The field to sort by
 * @param  int|null $guestId      Optional $guestId to find guest specific information
 *                                This would be more useful with a recursive sort, instead of top level sort
 *
 * @return Collection             Returns all guest information, but now sorted by the given key
 */
function sortGuestsInfo(Array $guests, string $key, int $guestId = null)
{
    $guests = scopeGuests($guests, $guestId);

    $results = searchCollection($guests, $key);
    if ($results->count()) return $guests->sortBy($key);

    $booking = $guests->flatMap(function ($guest) {
        return isset($guest['guest_booking']) ? $guest['guest_booking'] : null;
    });
    $results = searchCollection($booking, $key);
    if ($results->count()) return $guests->sortBy('guest_booking.*.' . $key);

    $accounts = $guests->flatMap(function ($guest) {
        return isset($guest['guest_account']) ? $guest['guest_account'] : null;
    });
    $results = searchCollection($accounts, $key);
    if ($results->count()) return $guests->sortBy('guest_account.*.' . $key);

    return collect(['status' => 404, 'message' => 'unable to sort guest information']);
}

/**
 * Wraps the guest array in a collection and
 * optionally filters down to a given user id
 *
 * @param  Array    $guests
 * @param  int|null $guestId
 *
 * @return Collection   The filtered guest array wrapped in a collection
 */
function scopeGuests(Array $guests, int $guestId = null)
{
    if (isset($guestId)) {
        return collect($guests)->filter(function ($guest) use ($guestId) {
            return isset($guest['guest_id']) ? $guest['guest_id'] == $guestId : false;
        });
    }

    return collect($guests);
}

/**
 * Abstracted functionality to pull out the key information into a new collection
 *
 * @param  Collection $coll         The collection to be looked through
 * @param  string     $key          The key that you are looking for
 *
 * @return Collection
 */
function searchCollection(Collection $coll, string $key)
{
    return $coll->pluck($key)
                ->filter(function ($value) {
                    return $value != null;
                });
}
