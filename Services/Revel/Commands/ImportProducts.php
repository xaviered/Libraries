<?php

namespace ixavier\Libraries\Services\Revel\Commands;

use Illuminate\Console\Command;
use ixavier\Libraries\Services\Revel\RevelUp;
use ixavier\Libraries\Services\Revel\RevelUpMapper;

class ImportProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports products from RevelUp system';

    /**
     * @var RevelUp
     */
    protected $revelClient;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $url = 'https://donatospol.revelup.com';
        $creds = ['user' => 'edison@donatospol.com', 'pass' => 'Donatos606!'];
        $this->revelClient = new RevelUp(new RevelUpMapper($url, $creds));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $products = $this->revelClient->getProducts();
    }
}
