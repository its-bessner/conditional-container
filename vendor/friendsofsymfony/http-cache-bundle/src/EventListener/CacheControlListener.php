<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\EventListener;

use FOS\HttpCacheBundle\Http\RuleMatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Set caching settings on matching response according to the configurations.
 *
 * The first matching ruleset is applied.
 *
 * @author Lea Haensenberger <lea.haensenberger@gmail.com>
 * @author David Buchmann <mail@davidbu.ch>
 */
final class CacheControlListener implements EventSubscriberInterface
{
    public const DEFAULT_TTL_HEADER_NAME = 'X-Reverse-Proxy-TTL';

    /**
     * Whether to skip this response and not set any cache headers.
     */
    private bool $skip = false;

    /**
     * Cache control directives directly supported by Response.
     */
    private array $supportedDirectives = [
        'max_age' => true,
        's_maxage' => true,
        'private' => true,
        'public' => true,
    ];

    /**
     * @var array List of arrays with RuleMatcherInterface, settings array
     */
    private array $rulesMap = [];

    public function __construct(
        /**
         * If not empty, add a debug header with that name to all responses,
         * telling the cache proxy to add debug output.
         *
         * @var string|false Name of the header or false to add no header
         */
        private readonly string|false $debugHeader = false,
        private readonly string $ttlHeader = self::DEFAULT_TTL_HEADER_NAME,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 10],
        ];
    }

    /**
     * Set whether to skip this response completely.
     *
     * This can be called when other parts of the application took care of all
     * cache headers. No attempt to merge cache headers is made anymore.
     *
     * The debug header is still added if configured.
     */
    public function setSkip(bool $skip = true): void
    {
        $this->skip = $skip;
    }

    /**
     * Apply the header rules if the request matches.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($this->debugHeader) {
            $response->headers->set($this->debugHeader, '1', false);
        }

        // do not change cache directives on non-cacheable requests.
        if ($this->skip || !$request->isMethodCacheable()) {
            return;
        }

        $options = $this->matchRule($request, $response);

        if (false === $options) {
            return;
        }

        if (!empty($options['cache_control'])) {
            $directives = array_intersect_key($options['cache_control'], $this->supportedDirectives);
            $extraDirectives = array_diff_key($options['cache_control'], $directives);
            if (!empty($directives)) {
                $this->setCache($response, $directives, $options['overwrite']);
            }
            if (!empty($extraDirectives)) {
                $this->setExtraCacheDirectives($response, $extraDirectives, $options['overwrite']);
            }
        }

        if (array_key_exists('reverse_proxy_ttl', $options)
            && null !== $options['reverse_proxy_ttl']
            && !$response->headers->has($this->ttlHeader)
        ) {
            $response->headers->set($this->ttlHeader, $options['reverse_proxy_ttl'], false);
        }

        if (!empty($options['vary'])) {
            $response->setVary($options['vary'], $options['overwrite']);
        }

        if (!empty($options['etag'])
            && ($options['overwrite'] || null === $response->getEtag())
        ) {
            $response->setEtag(md5($response->getContent()), 'weak' === $options['etag']);
        }
        if (isset($options['last_modified'])
            && ($options['overwrite'] || null === $response->getLastModified())
        ) {
            $response->setLastModified(new \DateTime($options['last_modified']));
        }
    }

    /**
     * Add a rule matcher with a list of header directives to apply if the
     * request and response are matched.
     *
     * @param RuleMatcherInterface $ruleMatcher The headers apply to request and response matched by this matcher
     * @param array                $settings    An array of header configuration
     */
    public function addRule(
        RuleMatcherInterface $ruleMatcher,
        array $settings = [],
    ): void {
        $this->rulesMap[] = [$ruleMatcher, $settings];
    }

    /**
     * Return the settings for the current request if any rule matches.
     *
     * @return array|false Settings to apply or false if no rule matched
     */
    private function matchRule(Request $request, Response $response): false|array
    {
        foreach ($this->rulesMap as $elements) {
            if ($elements[0]->matches($request, $response)) {
                return $elements[1];
            }
        }

        return false;
    }

    /**
     * Set cache headers on response.
     *
     * @param bool $overwrite Whether to keep existing cache headers or to overwrite them
     */
    private function setCache(Response $response, array $directives, bool $overwrite): void
    {
        if ($overwrite) {
            $response->setCache($directives);

            return;
        }

        if (str_contains($response->headers->get('Cache-Control', ''), 'no-cache')) {
            // this single header is set by default. if its the only thing, we override it.
            $response->setCache($directives);

            return;
        }

        foreach (array_keys($this->supportedDirectives) as $key) {
            $directive = str_replace('_', '-', $key);
            if ($response->headers->hasCacheControlDirective($directive)) {
                $directives[$key] = $response->headers->getCacheControlDirective($directive);
            }
            if ('public' === $directive && $response->headers->hasCacheControlDirective('private')
                || 'private' === $directive && $response->headers->hasCacheControlDirective('public')
            ) {
                unset($directives[$key]);
            }
        }

        $response->setCache($directives);
    }

    /**
     * Add extra cache control directives on response.
     *
     * @param bool $overwrite Whether to keep existing cache headers or to overwrite them
     */
    private function setExtraCacheDirectives(Response $response, array $controls, bool $overwrite): void
    {
        $flags = ['must_revalidate', 'proxy_revalidate', 'no_transform', 'no_cache', 'no_store'];
        $options = ['stale_if_error', 'stale_while_revalidate'];

        foreach ($flags as $key) {
            $flag = str_replace('_', '-', $key);
            if (!empty($controls[$key])
                && ($overwrite || !$response->headers->hasCacheControlDirective($flag))
            ) {
                $response->headers->addCacheControlDirective($flag);
            }
        }

        foreach ($options as $key) {
            $option = str_replace('_', '-', $key);
            if (isset($controls[$key])
                && ($overwrite || !$response->headers->hasCacheControlDirective($option))
            ) {
                $response->headers->addCacheControlDirective($option, $controls[$key]);
            }
        }
    }
}
