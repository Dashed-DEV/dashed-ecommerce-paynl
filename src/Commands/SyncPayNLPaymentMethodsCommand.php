<?php

namespace Dashed\DashedEcommercePaynl\Commands;

use Illuminate\Console\Command;
use Dashed\DashedEcommercePaynl\Classes\PayNL;

class SyncPayNLPaymentMethodsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashed:sync-paynl-payment-methods';

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
