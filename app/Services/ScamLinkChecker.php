<?php

namespace App\Services;

use App\Models\ScamLink;
use Illuminate\Support\Collection;

/**
 * Compares links with our internal list of scam domains ("what we do not process").
 *
 * - a link is reduced to its host: scheme, user info, port, path, query, "www." and the case are ignored,
 *   defanged links (hxxps://site[.]com) are understood;
 * - a list entry blocks the domain itself and all its subdomains (not "notsite.com", not "site.com.evil.org");
 * - an entry without a dot is a keyword and blocks every host that contains it.
 */
class ScamLinkChecker
{
    public const MIN_KEYWORD_LENGTH = 4;

    public const MAX_INPUT_LENGTH = 50000;

    /**
     * Reduce a link / domain to the form stored and compared, or null when it is not a domain or keyword.
     */
    public static function normalize(string $raw): ?string
    {
        $value = str_ireplace(['hxxps://', 'hxxp://', 'hxxps:', 'hxxp:'], ['https://', 'http://', 'https:', 'http:'], $raw);
        $value = str_ireplace(['[.]', '(.)', '{.}', '[dot]', '(dot)'], '.', $value);
        $value = trim($value, " \t\n\r\0\x0B\"'`<>()[]{}.,;:!?|*");

        $value = (string) preg_replace('#^[a-z][a-z0-9+.\-]*://#i', '', $value);
        $value = (string) preg_replace('#^//#', '', $value);
        $value = (string) preg_replace('#^[^/?\#@]*@#', '', $value);
        $value = preg_split('#[/?\\#]#', $value, 2)[0] ?? '';
        $value = (string) preg_replace('#:\d+$#', '', $value);
        $value = strtolower(rtrim($value, '.'));
        $value = (string) preg_replace('#^www\d*\.#', '', $value);

        if ($value === '') {
            return null;
        }

        if (function_exists('idn_to_ascii') && preg_match('/[^\x20-\x7e]/', $value)) {
            $ascii = idn_to_ascii($value, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            $value = $ascii !== false ? $ascii : $value;
        }

        return preg_match('/^[a-z0-9]([a-z0-9._-]*[a-z0-9])?$/', $value) === 1 ? $value : null;
    }

    /**
     * A domain has a dot, a keyword does not.
     */
    public static function isDomain(string $normalized): bool
    {
        return str_contains($normalized, '.');
    }

    /**
     * Check every link found in the text. Returns one row per distinct host:
     * ['input' => as written, 'host' => normalized, 'blocked' => bool, 'entry' => ?ScamLink, 'kind' => exact|subdomain|keyword|null]
     * Blocked rows come first.
     *
     * @return list<array{input: string, host: string, blocked: bool, entry: ?ScamLink, kind: ?string}>
     */
    public function check(string $text): array
    {
        $text = mb_substr($text, 0, self::MAX_INPUT_LENGTH);
        $entries = ScamLink::query()->get(['id', 'domain', 'note']);

        $domains = $entries->filter(fn (ScamLink $entry) => self::isDomain($entry->domain))->keyBy('domain');
        $keywords = $entries->reject(fn (ScamLink $entry) => self::isDomain($entry->domain))->values();

        $rows = [];
        $seen = [];

        foreach (preg_split('/[\s,;|]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            $host = self::normalize($token);

            if ($host === null || isset($seen[$host])) {
                continue;
            }

            $looksLikeLink = str_contains($host, '.') || str_contains($token, '://');
            [$entry, $kind] = $this->match($host, $domains, $keywords);

            // plain words are only interesting when they hit a keyword of the list
            if (! $looksLikeLink && $entry === null) {
                continue;
            }

            $seen[$host] = true;
            $rows[] = [
                'input' => trim($token, " \t\n\r\0\x0B\"'`<>()[]{},;|"),
                'host' => $host,
                'blocked' => $entry !== null,
                'entry' => $entry,
                'kind' => $kind,
            ];
        }

        usort($rows, fn (array $a, array $b) => ($b['blocked'] <=> $a['blocked']));

        return $rows;
    }

    /**
     * @param  Collection<string, ScamLink>  $domains
     * @param  Collection<int, ScamLink>  $keywords
     * @return array{0: ?ScamLink, 1: ?string}
     */
    private function match(string $host, Collection $domains, Collection $keywords): array
    {
        $labels = explode('.', $host);

        // the host itself, then its parent domains (sub.site.com -> site.com), never a bare top level domain
        for ($i = 0; $i < count($labels) - 1; $i++) {
            $candidate = implode('.', array_slice($labels, $i));

            if ($domains->has($candidate)) {
                return [$domains->get($candidate), $i === 0 ? 'exact' : 'subdomain'];
            }
        }

        foreach ($keywords as $keyword) {
            if (str_contains($host, $keyword->domain)) {
                return [$keyword, 'keyword'];
            }
        }

        return [null, null];
    }

    /**
     * Add the domains found in the text to the list.
     *
     * @return array{added: list<string>, duplicates: list<string>, covered: list<array{domain: string, by: string}>, invalid: list<string>}
     */
    public function import(string $text, ?string $note = null, ?int $userId = null): array
    {
        $text = mb_substr($text, 0, self::MAX_INPUT_LENGTH);
        $report = ['added' => [], 'duplicates' => [], 'covered' => [], 'invalid' => []];
        $note = filled($note) ? mb_substr(trim($note), 0, 500) : null;

        $existing = ScamLink::query()->pluck('domain')->flip();
        $batch = [];

        foreach (preg_split('/[\s,;|]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            $domain = self::normalize($token);

            $valid = $domain !== null && (self::isDomain($domain) || (mb_strlen($domain) >= self::MIN_KEYWORD_LENGTH && preg_match('/^[a-z0-9-]+$/', $domain) === 1));

            if (! $valid) {
                $report['invalid'][] = trim($token);

                continue;
            }

            if ($existing->has($domain) || isset($batch[$domain])) {
                $report['duplicates'][] = $domain;

                continue;
            }

            // already blocked through a parent domain of the list
            $parent = $this->coveredBy($domain, $existing, $batch);
            if ($parent !== null) {
                $report['covered'][] = ['domain' => $domain, 'by' => $parent];

                continue;
            }

            $batch[$domain] = [
                'domain' => $domain,
                'note' => $note,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $report['added'][] = $domain;
        }

        foreach (array_chunk(array_values($batch), 200) as $chunk) {
            ScamLink::query()->insert($chunk);
        }

        return $report;
    }

    /**
     * @param  Collection<string, int>  $existing
     * @param  array<string, mixed>  $batch
     */
    private function coveredBy(string $domain, Collection $existing, array $batch): ?string
    {
        if (! self::isDomain($domain)) {
            return null;
        }

        $labels = explode('.', $domain);

        for ($i = 1; $i < count($labels) - 1; $i++) {
            $parent = implode('.', array_slice($labels, $i));

            if ($existing->has($parent) || isset($batch[$parent])) {
                return $parent;
            }
        }

        return null;
    }
}
