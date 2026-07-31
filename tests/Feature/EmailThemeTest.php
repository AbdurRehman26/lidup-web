<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ActivationKeyIssued;
use Tests\TestCase;

class EmailThemeTest extends TestCase
{
    public function test_notification_emails_render_with_the_lidup_theme(): void
    {
        $user = new User([
            'name' => 'Syd',
            'email' => 'syd@example.com',
        ]);

        $html = (new ActivationKeyIssued('LIDUP-TEST-KEY'))
            ->toMail($user)
            ->render();

        $this->assertStringContainsString('LidUp', $html);
        $this->assertStringContainsString('Your Mac, still working', $html);
        $this->assertStringContainsString('#6f4ed8', $html);
        $this->assertStringContainsString('Lidup your Mac.', $html);
    }
}
