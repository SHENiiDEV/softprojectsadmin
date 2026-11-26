<?php

namespace Tests\Feature;

use App\Livewire\GlobalSearch;
use App\Livewire\ScamLinks\Index;
use App\Models\ActivityLog;
use App\Models\ScamLink;
use App\Models\User;
use App\Services\ScamLinkChecker;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScamLinksTest extends TestCase
{
    use RefreshDatabase;

    /** A part of the real list we do not process. */
    private const LIST = <<<'TXT'
monixbet.com
betybet.com
hugocasino.com
rx.casino
flagman.casino
mystake.com/en
tikitaka-casino.co.uk
garrisonbet.org.uk
1win.casino
twinkywin777.com 
highflybet
honeybets
magicwin247.bet
TXT;

    protected User $admin;

    protected User $worker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        config(['features.scam_links' => true]);

        $this->admin = User::factory()->create()->assignRole('admin');
        $this->worker = User::factory()->create()->assignRole('worker');
    }

    private function filled(): ScamLinkChecker
    {
        $checker = app(ScamLinkChecker::class);
        $checker->import(self::LIST, 'online casino', $this->admin->id);

        return $checker;
    }

    // ------------------------------------------------------------------ normalizing

    /**
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function normalizations(): array
    {
        return [
            'plain domain' => ['monixbet.com', 'monixbet.com'],
            'trailing space' => ['twinkywin777.com ', 'twinkywin777.com'],
            'with path' => ['mystake.com/en', 'mystake.com'],
            'full url' => ['https://www.MyStake.com/en/slots?x=1#top', 'mystake.com'],
            'www with digit' => ['www2.site.com', 'site.com'],
            'port and user' => ['http://user:pass@site.com:8080/x', 'site.com'],
            'protocol relative' => ['//site.com/a', 'site.com'],
            'trailing dot and comma' => ['site.com.,', 'site.com'],
            'defanged' => ['hxxps://site[.]com/path', 'site.com'],
            'brackets' => ['(https://site.com/a)', 'site.com'],
            'keyword' => ['HighFlyBet', 'highflybet'],
            'two level tld' => ['tikitaka-casino.co.uk', 'tikitaka-casino.co.uk'],
            'empty' => ['   ', null],
            'only punctuation' => ['...', null],
            'spaces inside' => ['not a domain', null],
            'underscore host is fine' => ['my_site.com', 'my_site.com'],
            'garbage chars' => ['$$$.com', null],
        ];
    }

    #[DataProvider('normalizations')]
    public function test_links_are_reduced_to_their_domain(string $input, ?string $expected): void
    {
        $this->assertSame($expected, ScamLinkChecker::normalize($input));
    }

    // ------------------------------------------------------------------ importing

    public function test_the_whole_list_can_be_pasted_at_once(): void
    {
        $report = app(ScamLinkChecker::class)->import(self::LIST, 'online casino', $this->admin->id);

        $this->assertCount(13, $report['added']);
        $this->assertSame([], $report['duplicates']);
        $this->assertSame([], $report['invalid']);
        $this->assertContains('mystake.com', $report['added'], 'the path of "mystake.com/en" is cut off');
        $this->assertContains('twinkywin777.com', $report['added'], 'the trailing space is ignored');
        $this->assertContains('highflybet', $report['added']);

        $this->assertSame(13, ScamLink::count());
        $this->assertSame('online casino', ScamLink::where('domain', 'monixbet.com')->value('note'));
        $this->assertSame($this->admin->id, ScamLink::where('domain', 'monixbet.com')->value('created_by'));
    }

    public function test_import_reports_duplicates_covered_subdomains_and_invalid_entries(): void
    {
        $checker = $this->filled();

        $report = $checker->import("monixbet.com\nHTTPS://www.MONIXBET.com/x\nplay.monixbet.com\nnew-scam.com\nab\n$$$\nnew-scam.com", null, $this->admin->id);

        $this->assertSame(['new-scam.com'], $report['added']);
        $this->assertSame(['monixbet.com', 'monixbet.com', 'new-scam.com'], $report['duplicates']);
        $this->assertSame([['domain' => 'play.monixbet.com', 'by' => 'monixbet.com']], $report['covered']);
        $this->assertSame(['ab', '$$$'], $report['invalid'], 'a keyword needs at least 4 characters');
        $this->assertSame(14, ScamLink::count());
    }

    public function test_separators_and_big_lists_are_handled(): void
    {
        $domains = collect(range(1, 450))->map(fn (int $i) => "scam-site-{$i}.com");

        $report = app(ScamLinkChecker::class)->import($domains->take(150)->implode("\n").', '.$domains->slice(150, 150)->implode('; ').' '.$domains->slice(300)->implode("\t"));

        $this->assertCount(450, $report['added']);
        $this->assertSame(450, ScamLink::count());
    }

    // ------------------------------------------------------------------ checking

    public function test_links_on_the_list_are_found_whatever_their_form(): void
    {
        $checker = $this->filled();

        foreach ([
            'monixbet.com' => 'exact',
            'https://www.monixbet.com/en/casino?ref=1' => 'exact',
            'HTTP://MONIXBET.COM' => 'exact',
            'play.monixbet.com' => 'subdomain',
            'a.b.monixbet.com/x' => 'subdomain',
            'hxxps://monixbet[.]com' => 'exact',
            'https://mystake.com/ru/slots' => 'exact',
            'highflybet.net' => 'keyword',
            'https://www.mynewhoneybets.org/play' => 'keyword',
            'tikitaka-casino.co.uk/bonus' => 'exact',
        ] as $link => $kind) {
            $rows = $checker->check($link);

            $this->assertCount(1, $rows, $link);
            $this->assertTrue($rows[0]['blocked'], $link);
            $this->assertSame($kind, $rows[0]['kind'], $link);
        }
    }

    public function test_look_alike_domains_are_not_blocked(): void
    {
        $checker = $this->filled();

        foreach (['notmonixbet.com', 'monixbet.com.evil.org', 'monixbet.net', 'xmonixbet.com', 'betybet.co', 'garrisonbet.com', 'bet.com', 'org.uk', 'co.uk', 'example.com'] as $link) {
            $rows = $checker->check($link);

            $this->assertFalse($rows[0]['blocked'], $link);
        }
    }

    public function test_a_pasted_text_with_many_links_reports_each_once_with_the_matched_entry_first(): void
    {
        $checker = $this->filled();

        $rows = $checker->check(<<<'TXT'
Hello, please approve these sites:
https://good-shop.com/page, www.another-good.org; monixbet.com
and also (https://www.rx.casino/en) plus good-shop.com again.
Thanks!
TXT);

        $this->assertSame(['monixbet.com', 'rx.casino', 'good-shop.com', 'another-good.org'], array_column($rows, 'host'));
        $this->assertSame([true, true, false, false], array_column($rows, 'blocked'));
        $this->assertSame('rx.casino', $rows[1]['entry']->domain);
        $this->assertSame('online casino', $rows[1]['entry']->note);
        $this->assertSame('https://www.rx.casino/en', $rows[1]['input'], 'shown as it was written, without the surrounding bracket');
    }

    public function test_plain_words_are_ignored_unless_they_hit_a_keyword(): void
    {
        $checker = $this->filled();

        $this->assertSame([], $checker->check('Please approve the following campaign'));
        $this->assertSame(['highflybet'], array_column($checker->check('please check highflybet today'), 'host'));
    }

    public function test_checking_with_an_empty_list_or_empty_text_is_safe(): void
    {
        $this->assertSame([], app(ScamLinkChecker::class)->check(''));
        $this->assertFalse(app(ScamLinkChecker::class)->check('a.com')[0]['blocked']);
    }

    // ------------------------------------------------------------------ page

    public function test_page_is_for_people_with_the_permission_and_follows_the_feature_flag(): void
    {
        $this->get(route('scam-links.index'))->assertRedirect('/login');

        $this->actingAs($this->worker)->get(route('scam-links.index'))->assertOk();

        $nobody = User::factory()->create();
        $this->actingAs($nobody)->get(route('scam-links.index'))->assertForbidden();

        config(['features.scam_links' => false]);
        $this->actingAs($this->admin)->get(route('scam-links.index'))->assertNotFound();
    }

    public function test_pasting_links_shows_an_error_with_the_matched_entry(): void
    {
        $this->filled();

        Livewire::actingAs($this->worker)->test(Index::class)
            ->assertDontSee('Do not approve')
            ->set('checkText', "https://www.monixbet.com/en\nhttps://fine-site.com")
            ->assertSee('Error: 1 of 2 links is on the scam list. Do not approve.')
            ->assertSee('exact match')
            ->assertSee('monixbet.com')
            ->assertSee('online casino')
            ->assertSee('fine-site.com')
            ->assertSee('Copy blocked (1)')
            ->assertSee('Copy clean (1)')
            ->set('checkText', 'https://fine-site.com https://another.org')
            ->assertSee('All clear: none of the 2 links is on the scam list.')
            ->assertDontSee('Do not approve')
            ->call('clearCheck')
            ->assertSet('checkText', '');
    }

    public function test_the_page_says_when_no_links_were_found(): void
    {
        Livewire::actingAs($this->worker)->test(Index::class)
            ->set('checkText', 'just some words without any links')
            ->assertSee('No links found in the text.');
    }

    public function test_links_are_added_from_the_page_with_a_report_and_a_log_entry(): void
    {
        Livewire::actingAs($this->admin)->test(Index::class)
            ->set('newLinks', "monixbet.com\nbetybet.com\n!!!\n")
            ->set('newNote', 'casino')
            ->call('addLinks')
            ->assertHasNoErrors()
            ->assertSee('Added')
            ->assertSee('not recognised: 1')
            ->assertSet('newLinks', '!!!')
            ->assertSee('monixbet.com')
            ->assertSee('casino')
            // adding the same again only reports duplicates
            ->set('newLinks', 'monixbet.com')
            ->call('addLinks')
            ->assertSee('already in the list: 1')
            ->assertSet('newLinks', '')
            ->assertSet('newNote', '');

        $this->assertSame(2, ScamLink::count());
        $this->assertTrue(ActivityLog::where('action', 'scam_links_added')->where('description', 'like', '%monixbet.com%')->exists());
    }

    public function test_adding_nothing_is_refused(): void
    {
        Livewire::actingAs($this->admin)->test(Index::class)
            ->set('newLinks', '   ')
            ->call('addLinks')
            ->assertHasErrors(['newLinks']);
    }

    public function test_entries_can_be_searched_noted_and_removed(): void
    {
        $this->filled();
        $link = ScamLink::where('domain', 'rx.casino')->firstOrFail();

        $component = Livewire::actingAs($this->admin)->test(Index::class)
            ->set('search', 'flagman')
            ->assertSee('flagman.casino')
            ->assertDontSee('betybet.com')
            ->assertDontSee('rx.casino')
            ->set('search', 'zzz-nothing')
            ->assertSee('Nothing in the list matches')
            ->set('search', '')
            ->call('updateNote', $link->id, '  reason: casino  ');

        $this->assertSame('reason: casino', $link->fresh()->note);

        $component->call('updateNote', $link->id, '')->call('deleteLink', $link->id);

        $this->assertNull(ScamLink::find($link->id));
        $this->assertTrue(ActivityLog::where('action', 'scam_link_removed')->where('description', 'like', '%rx.casino%')->exists());
    }

    public function test_search_treats_wildcards_literally(): void
    {
        ScamLink::factory()->create(['domain' => 'plain.com', 'note' => 'a']);
        ScamLink::factory()->create(['domain' => 'percent.com', 'note' => '100% fake']);

        Livewire::actingAs($this->admin)->test(Index::class)
            ->set('search', '100%')
            ->assertSee('percent.com')
            ->assertDontSee('plain.com');
    }

    public function test_workers_can_check_but_not_change_the_list(): void
    {
        $this->filled();
        $link = ScamLink::firstOrFail();

        Livewire::actingAs($this->worker)->test(Index::class)
            ->assertDontSee('Add to the list')
            ->call('addLinks')
            ->assertForbidden();

        Livewire::actingAs($this->worker)->test(Index::class)->call('deleteLink', $link->id)->assertForbidden();
        Livewire::actingAs($this->worker)->test(Index::class)->call('updateNote', $link->id, 'x')->assertForbidden();

        $this->assertSame(13, ScamLink::count());
        $this->assertNotSame('x', $link->fresh()->note);
    }

    public function test_the_list_is_paged(): void
    {
        ScamLink::factory()->count(35)->create();

        Livewire::actingAs($this->admin)->test(Index::class)
            ->assertViewHas('links', fn ($links) => $links->count() === 30 && $links->total() === 35);
    }

    public function test_global_search_finds_list_entries_only_for_people_who_may_see_them(): void
    {
        $this->filled();

        $results = Livewire::actingAs($this->worker)->test(GlobalSearch::class)->set('query', 'monix')->get('results');
        $this->assertSame(['monixbet.com'], array_column($results['scam'], 'title'));

        $nobody = User::factory()->create();
        $results = Livewire::actingAs($nobody)->test(GlobalSearch::class)->set('query', 'monix')->get('results');
        $this->assertSame([], $results['scam']);
    }

    public function test_menu_item_follows_the_permission(): void
    {
        $labels = fn (User $user) => collect(app(\App\Services\NavigationMenu::class)->groups($user))->flatMap(fn ($group) => $group['items'])->pluck('label');

        $this->assertTrue($labels($this->worker)->contains('Scam Links'));
        $this->assertFalse($labels(User::factory()->create())->contains('Scam Links'));
    }

    public function test_roles_get_the_expected_permissions(): void
    {
        $this->assertTrue($this->admin->can('manage_scam_links'));
        $this->assertTrue(User::factory()->create()->assignRole('curator')->can('manage_scam_links'));
        $this->assertTrue($this->worker->can('view_scam_links'));
        $this->assertFalse($this->worker->can('manage_scam_links'));
    }
}
