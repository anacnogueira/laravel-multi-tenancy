<?php

namespace App;

use Hyn\Tenancy\Environment;
use Hyn\Tenancy\Models\Hostname;
use Hyn\Tenancy\Models\Website;
use Illuminate\Support\Facades\Hash;
use Hyn\Tenancy\Contracts\Repositories\HostnameRepository;
use Hyn\Tenancy\Contracts\Repositories\WebsiteRepository;
use App\User;

class Tenant
{

  static $domain;

  public function __construct(Hostname $hostname, Website $website = null,  User $admin = null)
  {
    $this->hostname = $hostname;
    $this->website = $website ?? $hostname->website->first();
    $this->admin = $admin;
  }

  public static function getDomain()
  {
    $baseUrl = config('app.url');
    self::$domain = parse_url($baseUrl)['host'];
    return self::$domain;
  }

  public function delete()
  {
    app(HostnameRepository::class)->delete($this->hostname, true);
    app(WebsiteRepository::class)->delete($this->website, true);
  }

  public static function createFrom($name, $email): Tenant
  {
    $website = new Website;
    $website->uuid = $name."_".str_random(10);
    app(WebsiteRepository::class)->create($website);

    $hostname = new Hostname;

    $domain = self::getDomain();

    $hostname->fqdn = "{$name}.{$domain}";
    app(HostnameRepository::class)->attach($hostname, $website);
    self::connectTenant($hostname);

    $admin = static::makeAdmin($name, $email, str_random());
    return new Tenant($hostname, $website, $admin);
  }

  private static function makeAdmin($name, $email, $password): User
  {
    $admin = User::create(['name' => $name, 'email' => $email, 'password' => Hash::make($password)]);
    $admin->guard_name = 'web';
    $admin->assignRole('admin');
    return $admin;
  }

  public static function retrieveBy($name): ?Tenant
  {
    $domain = self::getDomain();
    $fqdn = "{$name}.{$domain}";

    if ($hostname = Hostname::where('fqdn', $fqdn)->with(['website'])->first()) {
        return new Tenant($hostname);
    }

    return null;
  }

  private static function connectTenant($hostname)
  {
    $tenancy = app(Environment::class);
    $tenancy->hostname($hostname);
    $website = $hostname->website;
    $tenancy->hostname();
    $tenancy->tenant($website);
  }
}
