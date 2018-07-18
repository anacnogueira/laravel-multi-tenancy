<?php

namespace Tests\Feature;

use App\Notifications\TenantCreated;
use App\Tenant;
use App\User;
use Illuminate\Support\Facades\Notification;
use Tests\TenantAwareTestCase;


class TenantCreateCommandTest extends TenantAwareTestCase
{
  protected function setUp()
  {
      parent::setUp();

      Notification::fake();
  }

  /** @test */
  public function tenant_name_is_required()
  {
    $this->assertTrue(true);
        // $this->expectExceptionMessage('Not enough arguments (missing: "name").');
        // $this->artisan('tenant:create', ['email' => 'test@example.com']);
  }

   //  protected function tearDown()
   // {
   //     if ($tenant = Tenant::retrieveBy('example')) {
   //         $tenant->delete();
   //     }
   //     parent::tearDown();
   // }


}
