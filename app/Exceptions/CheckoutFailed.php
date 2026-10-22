<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown inside the checkout transaction to force a rollback, so ticket stock
 * that was reserved for earlier cart lines is given back when a later line
 * cannot be reserved.
 */
class CheckoutFailed extends RuntimeException {}
