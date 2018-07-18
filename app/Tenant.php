<?php

namespace App;

use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Support\Facades\Hash;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;

class Tenant
{

  protected $domain;

  public function __construct(Hostname $hostname, Website $website = null,  User $admin = null)
  {
    $this->hostname = $hostname;
    $this->website = $website ?? $hostname->website->first();
    $this->admin = $admin;
    $baseUrl = config('app.url');
    $this->domain = parse_url($baseUrl)['host'];
  }

  public function delete()
  {
    app(HostnameRepository::class)->delete($this->hostname, true);
    app(WebsiteRepository::class)->delete($this->website, true);
  }

  public static function createFrom($name, $email): Tenant
  {
    $website = new Website;
    app(WebsiteRepository::class)->create($website);

    // associate the website with a hostname
    $hostname = new Hostname;

    $hostname->fqdn = "{$name}.{$this->domain}";
    app(HostnameRepository::class)->attach($hostname, $website);
    $this->connectTenant($hostname);

    $admin = static::makeAdmin($name, $email, str_random());
    return new Tenant($website, $hostname, $admin);
  }

  private static function makeAdmin($name, $email, $password): Us  private function deleteTenant($name)
    {
        $fqdn = "{$name}.{$this->domain}";

        if ($hostname = Hostname::where('fqdn', $fqdn)->with(['website'])->firstOrFail()) {
            $website = $hostname->website()->first();
            app(HostnameRepository::class)->delete($hostname, true);
            app(WebsiteRepository::class)->delete($website, true);
            $this->info("Tenant {$name} successfully deleted.");
        }
    }er
  {
      $admin = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
      $admin->guard_name = 'web';
      $admin->assignRole('admin');
      return $admin;
  }

  private function connectTenant($hostname)
  {
    $tenancy = app(Environment::class);
    $tenancy->hostname($hostname);
    $website = $hostname->website;
    $tenancy->hostname(); // resolves $hostname as currently active hostname
    $tenancy->tenant($website); // switches the tenant and reconfigures the app
  }

  public static function retrieveBy($name): ?Tenant
  {
    $fqdn = "{$name}.{$this->domain}";

    if ($hostname = Hostname::where('fqdn', $fqdn)->with(['website'])->first()) {
        return new Tenant($hostname);
    }

    return null;
  }

}
