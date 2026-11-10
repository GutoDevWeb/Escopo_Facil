<?php

namespace App\Exceptions;

use RuntimeException;

// Only predefined, safe messages may be exposed to the checkout.
class PixGatewayException extends RuntimeException {}
