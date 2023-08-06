<?php

namespace Dashed\DashedEcommercePaynl\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Dashed\DashedEcommercePaynl\DashedEcommercePaynl
 */
class DashedEcommercePaynl extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'dashed-ecommerce-paynl';
    }
}
