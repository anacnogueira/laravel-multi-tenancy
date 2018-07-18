<?php

namespace App\Console\Commands;

use App\Notifications\TenantCreated;
use App\Tenant;
use Hyn\Tenancy\Models\Customer;
use Illuminate\Console\Command;

class CreateTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create {name} {email}';
    protected $domain;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a tenant with the provided name and email address e.g. php artisan tenant:create company company@example.com';

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
        $name = $this->argument('name');
        $email = $this->argument('email');

        if ($this->tenantExists($name)) {
            $this->error("A Tenant with name '{$name}' already exists");

            return;
        }    

        $tenant = Tenant::createFrom($name, $email);
        $this->info("Tenant '{$name}' is created and is now accessible at {$tenant->hostname->fqdn}");
        // invite admin
        $tenant->admin->notify(new TenantCreated($tenant->hostname));
        $this->info("Admin {$email} has been invited!");
          
    }

    private function tenantExists($name)
    {
        
        $fqdn = "{$name}.{$this->domain}";

        return Hostname::where('fqdn', $fqdn)->exists();
    }

    private function registerTenant($name)
    {
        $website = new Website;
        app(WebsiteRepository::class)->create($website);

        $hostname = new Hostname;
        $hostname->fqdn = "{$name}.{$this->domain}";
        app(HostnameRepository::class)->attach($hostname, $website);

        return $hostname;
    }

    private function connectTenant($hostname)
    {
        $tenancy = app(Environment::class);
        $tenancy->hostname($hostname); 
        $website = $hostname->website;
        $tenancy->hostname(); // resolves $hostname as currently active hostname
        $tenancy->tenant($website); // switches the tenant and reconfigures the app       
    }

    private function addAdmin($name, $email, $password)
    {
        $admin = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
        $admin->guard_name = 'web';
        $admin->assignRole('admin');
        return $admin;
    }
}
