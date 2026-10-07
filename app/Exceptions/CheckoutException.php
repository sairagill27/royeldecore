<?php

namespace App\Exceptions;

use Exception;

// Used only for messages that are SAFE to show the customer,
// such as "only 2 left in stock". Other errors are never shown on screen.
class CheckoutException extends Exception {}
