<?php

namespace App\Console\Commands;

use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use Hyn\Tenancy\Environment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class DeleteTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:delete {name}';

    /**
     * The console command description.
     *
     * @var string
     */
     protected $description = 'Deletes a tenant of the provided name. Only available on the local environment e.g. php artisan tenant:delete boise';

     protected $domain;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $baseUrl = config('app.url');
        $this->domain = parse_url($baseUrl)['host'];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
         // because this is a destructive command, we'll only allow to run this command
        // if you are on the local environment
        if (!app()->isLocal()) {
            $this->error('This command is only avilable on the local environment.');
            return;
        }
        $name = $this->argument('name');
        $this->deleteTenant($name);
    }

    private function deleteTenant($name)
    {
        $fqdn = "{$name}.{$this->domain}";

        if ($hostname = Hostname::where('fqdn', $fqdn)->with(['website'])->firstOrFail()) {
            $website = $hostname->website()->first();
            app(HostnameRepository::class)->delete($hostname, true);
            app(WebsiteRepository::class)->delete($website, true);            
            $this->info("Tenant {$name} successfully deleted.");
        }
    }
}