<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A transient reverse-geocoding failure (timeout, 429, 5xx) that is worth
 * retrying later, as opposed to "no result for these coordinates".
 */
class GeocodingUnavailableException extends RuntimeException {}
