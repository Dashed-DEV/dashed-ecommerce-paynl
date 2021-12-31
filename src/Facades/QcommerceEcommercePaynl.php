<?php

namespace Qubiqx\QcommerceEcommercePaynl\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Qubiqx\QcommerceEcommercePaynl\QcommerceEcommercePaynl
 */
class QcommerceEcommercePaynl extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'qcommerce-ecommerce-paynl';
    }
}
