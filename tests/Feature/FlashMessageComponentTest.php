<?php

namespace Tests\Feature;

use Tests\TestCase;

class FlashMessageComponentTest extends TestCase
{
    public function test_renders_success_message_from_session(): void
    {
        session()->flash('message', 'Document successfully deleted.');

        $this->blade('<x-flash-message />')
            ->assertSee('Document successfully deleted.')
            ->assertSee('role="status"', false)
            ->assertSee('bg-emerald-50', false);
    }

    public function test_renders_error_message_from_session_with_alert_role(): void
    {
        session()->flash('error', 'You do not have permission.');

        $this->blade('<x-flash-message />')
            ->assertSee('You do not have permission.')
            ->assertSee('role="alert"', false)
            ->assertSee('bg-rose-50', false);
    }

    public function test_renders_nothing_without_flash_data(): void
    {
        $this->blade('<x-flash-message />')->assertDontSee('role=', false);
    }
}
