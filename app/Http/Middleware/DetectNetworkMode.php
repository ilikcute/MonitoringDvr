<?php

namespace App\Http\Middleware;

use App\Services\NetworkDetector;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectNetworkMode
{
    public function __construct(protected NetworkDetector $detector)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $networkType = $this->detector->detect($request);

        $request->attributes->set('network_type', $networkType);

        $response = $next($request);

        $response->headers->set('X-Network-Mode', $networkType);

        return $response;
    }
}
