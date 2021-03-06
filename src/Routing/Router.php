<?php

namespace Devio\Permalink\Routing;

use Illuminate\Support\Arr;
use Devio\Permalink\Permalink;
use Illuminate\Support\Collection;
use Illuminate\Routing\Router as LaravelRouter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Router extends LaravelRouter
{
    /**
     * Load the entire permalink collection.
     */
    public function loadPermalinks()
    {
        $permalinks = Permalink::with('entity')->get();

        $this->group(config('permalink.group'), function () use ($permalinks) {
            $this->addPermalinks($permalinks);
        });
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Routing\Route|void
     */
    public function findRoute($request)
    {
        // First we'll try to find any code defined route for the current request.
        // If no route was found, we can then attempt to find if the URL path
        // matches a existing permalink. If not just rise up the exception.
        try {
            return parent::findRoute($request);
        } catch (HttpException $e) {
            $this->findPermalink($request);
        }

        return parent::findRoute($request);
    }

    /**
     * Get the permalink for the current request if any.
     */
    public function findPermalink($request)
    {
        $path = trim($request->getPathInfo(), '/');

        if (! $permalink = Permalink::where('final_path', $path)->first()) {
            throw new NotFoundHttpException;
        }

        $this->group(config('permalink.group'), function () use ($permalink) {
            $this->addPermalinks($permalink);
        });
    }

    protected function createPermalinkRoute($permalink)
    {
        $route = $this->newPermalinkRoute($permalink);

        // If we have groups that need to be merged, we will merge them now after this
        // route has already been created and is ready to go. After we're done with
        // the merge we will be ready to return the route back out to the caller.
        if ($this->hasGroupStack()) {
            $this->mergeGroupAttributesIntoRoute($route);
        }

        $this->addWhereClausesToRoute($route);

        return $route;
    }

    /**
     * Create a new Route for the given permalink.
     *
     * @param $permalink
     * @return Route
     */
    protected function newPermalinkRoute($permalink)
    {
        $path = $this->prefix($permalink->final_path);
        $action = $this->convertToControllerAction($permalink->action);

        return tap($this->newRoute($permalink->method, $path, $action), function ($route) use ($permalink) {
            $route->setPermalink($permalink);
        });
    }

    /**
     * Add a collection of permalinks to the router.
     *
     * @param array $permalinks
     * @param bool $forceRefresh
     * @return Router
     */
    public function addPermalinks($permalinks = [], $forceRefresh = false)
    {
        if (! $permalinks instanceof Collection) {
            $permalinks = Arr::wrap($permalinks);
        }

        foreach ($permalinks as $permalink) {
            $this->addPermalink($permalink);
        }

        if ($forceRefresh || config('permalink.refresh_route_lookups')) {
            $this->refreshRouteLookups();
        }

        return $this;
    }

    /**
     * Add a single permalink to the router.
     *
     * @param $permalink
     * @return Router
     */
    protected function addPermalink($permalink)
    {
        if ($permalink->action) {
            $route = $this->createPermalinkRoute($permalink);

            $this->routes->add($route);
        }

        return $this;
    }

    /**
     * Create a new route.
     *
     * @param array|string $methods
     * @param string $uri
     * @param mixed $action
     * @return Route
     */
    protected function newRoute($methods, $uri, $action)
    {
        return (new Route($methods, $uri, $action))
            ->setRouter($this)
            ->setContainer($this->container);
    }

    /**
     * Refresh the route name and action lookups.
     */
    public function refreshRouteLookups()
    {
        $this->getRoutes()->refreshNameLookups();
        $this->getRoutes()->refreshActionLookups();
    }
}
