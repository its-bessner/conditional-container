<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A matcher similar to the Symfony RequestMatcher but also considering the
 * response to decide if a rule should apply to this response.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
interface RuleMatcherInterface
{
    /**
     * Check whether the request and response both match.
     */
    public function matches(Request $request, Response $response): bool;
}
