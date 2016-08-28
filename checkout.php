<?php

require_once 'vendor/autoload.php';

// The following code would be triggered by a series of actions taken by the user (i.e. Adding a new address)
// or pulled out of the database (i.e. Getting the items from our catalogue table).
// Here, I am building up the objects manually for the demonstration.

$home = new Address('123 Home Ct.', '', 'Small Town', 'KY', 12345);
$work = new Address('1000 Universal Blvd.', '', 'Orlando', 'FL', 33619);
$beach = new Address('50 Luau Ln.', '', 'Honalulu', 'HI', 86753);

$customer = new Customer('Ben', 'Wilson');
$customer->addAddress($home, 'home');
$customer->addAddress($work, 'work');
$customer->addAddress($beach, 'beach');
$customer->updateAddress('work', '1 Sourcetoad Lillypad.', 'Ste 105', 'Tampa', null, 33634);

$watch = new Item(1200, 'watch', 199.99);
$widget = new Item(1201, 'widget', 1997.97);
$wand = new Item(1202, 'wand', 39.99);

$cart = new Cart($customer);
$cart->addItem($watch, 2);
$cart->addItem($widget, 1);
$cart->addItem($wand, 1);

$cart->updateItemQuantity(1200, 1);
$cart->updateItemQuantity(1202, 4);
$cart->deleteItem(1201);

// Following Code block is messy!
// Just for demonstration purposes only

echo 'Customer Addresses <br>';
collect($customer->addresses)->each(function($address){
    echo $address->present() . '<br>';
});

echo '<br><br>';

echo 'Customer Name <br>';
echo $customer->name();

echo '<br><br>';

echo 'Catalog Prices <br>';
echo 'Watch $' . $watch->itemTotal() . '<br>';
echo 'Widget $' . $widget->itemTotal() . '<br>';
echo 'Wand $' . $wand->itemTotal() . '<br>';

echo '<br><br>';

echo 'Cart Items <br>';
echo '<pre>';
print_r($cart->items);
echo '</pre>';

echo '<br><br>';

echo 'Checkout <br>';
echo 'Subtotal $' . $cart->subtotal() . '<br>';
echo 'Estimated Tax $' . $cart->tax($cart->subtotal()) . '<br>';
echo 'Shipping Address ' . $cart->customer->getShippingAddress() . '<br>';
echo 'Shipping $' . $cart->shipping() . '<br>';
echo 'Total $' . $cart->total() . '<br>';

// Ok, back to clean(er) code

// In production, these classes would be broken out to their
// own file, namespaced, and then included at the top with "use"

class Address {
    public $address_1;
    public $address_2 = null;
    public $city;
    public $state;
    public $zip;

    /**
     * Build up our address object
     *
     * @param string $address_1 Street address
     * @param string $address_2 Apt # or PO Box
     * @param string $city
     * @param string $state
     * @param int    $zip
     */
    public function __construct(string $address_1, string $address_2, string $city, string $state, int $zip)
    {
        $this->address_1 = $address_1;
        $this->address_2 = $address_2;
        $this->city = $city;
        $this->state = $state;
        $this->zip = $zip;
    }

    /**
     * Format the address
     *
     * @return string
     */
    public function present()
    {
        strlen($this->address_2) > 0 ? $street = $this->address_1 . ' ' . $this->address_2 : $street = $this->address_1;

        return $street . ', ' . $this->city . ', ' . $this->state . ' ' . $this->zip;
    }
}

class Customer {
    public $first_name;
    public $last_name;
    public $addresses = [];
    protected $shipping_address;

    /**
     * @param string $first_name
     * @param string $last_name
     */
    public function __construct(string $first_name, string $last_name)
    {
        $this->first_name = $first_name;
        $this->last_name = $last_name;
    }

    /**
     * Format the customer's name
     *
     * @return string
     */
    public function name()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Add a new address that the user can use with their account.
     * Also, if a shipping address has not been specified,
     * the first address becomes the default shipping address.
     *
     * @param Address $address
     * @param string  $name    The tag to lookup the address again
     */
    public function addAddress(Address $address, string $name)
    {
        if ( ! isset($this->shipping_address)) $this->shipping_address = $name;
        $this->addresses[$name] = $address;
    }

    /**
     * Lookup an address by the name
     *
     * @param  string $name
     *
     * @return string
     */
    public function getAddress(string $name)
    {
        if (isset($this->addresses[$name])) return $this->addresses[$name]->present();
    }

    /**
     * Allows a customer to update their address for a given location.
     *
     * This could be cleaned up more by accepting an associative Array for the address,
     * instead of the address parameters individually. It feels like a lot of parameters.
     *
     * @param  string      $name         The location to be updated
     * @param  string|null $address_1    (optional) Remains the same if not provided
     * @param  string|null $address_2    (optional) Remains the same if not provided
     * @param  string|null $city         (optional) Remains the same if not provided
     * @param  string|null $state        (optional) Remains the same if not provided
     * @param  int|null    $zip          (optional) Remains the same if not provided
     *
     * @return string                   A quick, small message to let the user know if the address was updated
     */
    public function updateAddress(string $name, string $address_1 = null, string $address_2 = null, string $city = null, string $state = null, int $zip = null)
    {
        if (isset($this->addresses[$name])) {
            if (isset($address_1)) $this->addresses[$name]->address_1 = $address_1;
            if (isset($address_2)) $this->addresses[$name]->address_2 = $address_2;
            if (isset($city)) $this->addresses[$name]->city = $city;
            if (isset($state)) $this->addresses[$name]->state = $state;
            if (isset($zip)) $this->addresses[$name]->zip = $zip;

            return 'success';
        }

        return 'failed';
    }

    /**
     * Get the formatted shipping address
     *
     * @return string
     */
    public function getShippingAddress()
    {
        return $this->addresses[$this->shipping_address]->present();
    }

    /**
     * Update the shipping address if an address can be found with the given name
     *
     * @param  string $name         Name to search for in the addresses
     */
    public function updateShippingAddress(string $name)
    {
        if (isset($this->addresses[$name])) $this->shipping_address = $name;
    }
}

class Item {
    protected $id;
    protected $name;
    public $quantity;
    protected $price;

    /**
     * Build up a new item
     *
     * @param int    $id    Unique ID for each item
     * @param string $name  Item's name
     * @param float  $price Price of the item
     */
    public function __construct(int $id, string $name, float $price)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
    }

    /**
     * @return int Getter for the item's ID
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @return string Getter for the item's name
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return double Getter for the item's price
     */
    public function price()
    {
        return $this->price;
    }

    /**
     * Calculate a total for this item
     *
     * @return double
     */
    public function itemTotal()
    {
        return isset($this->quantity) ? $this->price * $this->quantity : $this->price;
    }
}

class Cart {
    public $customer;
    public $items = [];
    protected $tax_rate = .07;

    /**
     * A Cart must be instantiated with a Customer because
     * "an instance of a cart can have only one customer"
     *
     * @param Customer $customer
     */
    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    /**
     * Push a new item onto the items array
     * with a given quantity
     *
     * @param Item $item     [description]
     * @param int  $quantity [description]
     */
    public function addItem(Item $item, int $quantity)
    {
        $item->quantity = $quantity;
        array_push($this->items, $item);
    }

    /**
     * Update the quantity of an existing item in the cart.
     * If the quantity is set to zero, the item is deleted.
     * If the quantity is below zero, the call is ignored.
     *
     * @param  int    $id           Unique item ID
     * @param  int    $quantity     New quantity for the item
     */
    public function updateItemQuantity(int $id, int $quantity)
    {
        if ($quantity >= 0) {
            if ($quantity == 0) $this->deleteItem($quantity);
            $this->items[$this->findItemKey($id)]->quantity = $quantity;
        }
    }

    /**
     * Delete an item from the cart
     *
     * @param  int    $id           Unique item ID
     */
    public function deleteItem(int $id)
    {
        unset($this->items[$this->findItemKey($id)]);
    }

    /**
     * Find an item by it's ID and return the index
     * of the item in the items array.
     *
     * @param  int    $id           Unique item ID
     *
     * @return int Item index
     */
    private function findItemKey(int $id)
    {
        return collect($this->items)->map(function ($item, $key) use ($id) {
            if ($item->id() == $id) return $key;
        })->filter(function ($item) {
            return ! is_null($item);
        })->first();
    }

    /**
     * Cacluate subtotal of all the items in the cart
     *
     * @return double
     */
    public function subtotal()
    {
        return collect($this->items)->map(function ($item) {
            return $item->itemTotal();
        })->sum();
    }

    /**
     * Calculate tax based on the tax_rate set on the Cart class
     *
     * @param  double|int $subtotal
     *
     * @return double Tax to be paid
     */
    public function tax($subtotal)
    {
        return round($subtotal * $this->tax_rate, 2);
    }

    /**
     * Calculate shipping costs for where the items are to be delivered.
     *
     * In a real project I would use a shipping API like Shippo
     * and an HTTP Client like Guzzle to pull in an accurate price
     *
     * @return double Shipping price
     */
    public function shipping()
    {
        $to = $this->customer->getShippingAddress();
        $from = (new Address('22 Amazon Fullfillment Rd.', '', 'Orlando', 'FL', 33649))->present();

        // fake call to shipping api...
        // return $shippo->getShippingPrice($to, $from);

        // For now, just hardcode a shipping price
        return 7.49;
    }

    /**
     * Pull all the prices together to find the total amount to charge.
     *
     * @return double Total price of cart
     */
    public function total()
    {
        $total = $this->subtotal();
        $total += $this->tax($total);
        $total += $this->shipping();

        return round($total, 2);
    }
}
