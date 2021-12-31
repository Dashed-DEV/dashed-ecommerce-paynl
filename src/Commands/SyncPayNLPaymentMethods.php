<?php

namespace Qubiqx\QcommerceEcommercePaynl\Commands;

use Illuminate\Console\Command;
use Qubiqx\QcommerceEcommercePaynl\Classes\PayNL;

class SyncPayNLPaymentMethods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'qcommerce:sync-paynl-payment-methods';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync PayNL payment methods';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        PayNL::syncPaymentMethods();
    }
}
