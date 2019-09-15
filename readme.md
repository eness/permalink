# Advanced Laravel Permalinks and SEO Management from Database

[![Build Status](https://travis-ci.com/IsraelOrtuno/permalink.svg?branch=master)](https://travis-ci.com/IsraelOrtuno/permalink) [![Latest Stable Version](https://poser.pugx.org/devio/permalink/version)](https://packagist.org/packages/devio/permalink)

This package allows to create dynamic routes right from database, just like WordPress and other CMS do.

## Roadmap
- [ ] [Resources for visual SEO management](https://github.com/IsraelOrtuno/permalink-form) (in progress)

## Documentation
- [Installation](#installation)
- [Getting started](#getting-started)
- [Usage](#usage)
    - [Replace the default Router]()
    - [Creating a permalink]()
- [Route names](#route-names)
- [Getting the route for a resource](#getting-the-route-for-a-resource)
- [Routes and route groups](#routes-and-route-groups)
- [Nesting routes](#nesting-routes)
- [Creating/updating permalinks manually](#creatingupdating-permalinks-manually)
- [Overriding the default action](#overriding-the-default-action)
- [Support for morphMap & actionMap](#support-for-morphmap--actionmap)
- [Automatic SEO generation](#automatic-seo-generation)

## Installation

### Install the package

```shell
composer require devio/permalink
```

### Run the migrations

```shell
php artisan migrate
```

## Getting started

This package handles dynamic routing directly from our database. Nested routes are also supported, so we can easily create routes like this `/jobs/frontend-web-developer`.

Most of the solutions out there are totally bound to models with polymorphic relationships, however that's not flexible at all when dealing with routes without models. This package supports both, routes with bound models and regular routes.

Basically, the package stores routes in a `permalinks` table which contains information about every route: 
- Slug
- Parent (parent route for nesting)
- Model (if any)
- Action (controller action or model default action)
- SEO options (title, metas...)

### Example

Let's review a very basic example to understand how it works:

| id | slug          | parent_id | parent_for | entity_type        | entity_id        | action               | final_path            |
| -- | ------------- | --------- | ---------- | ------------------ | ---------------- | -------------------- | --------------------- |
| 1  | users         | NULL      | App\User   | NULL               | NULL             | UserController@index | users
| 2  | israel-ortuno | 1         | NULL       | App\User           | 1                | UserController@show  | users/israel-ortuno

It will run the following (this example tries to be as explicit as possible, internally it uses eager loading and some other performance optimizations):

```php
$router->get('users', 'UserController@index');
$router->get('users/israel-ortuno', 'UserController@show');

// Which will produce:
//    /users                UserController@index
//    /users/israel-ortuno  
```

**NOTE:** The `show` method will receive the user as parameter `App\User::find(1)` the route is bound to that model.

## Usage

### Replace default Router
This package has it's own router which extends the default Laravel router. To replace the default router for the one included in this package you have two options:

```shell
php artisan permalink:install {--default}
```

The console will propmpt you with 2 options:
```shell
  [0] Http/Kernel.php (Default & Recommended)
  [1] bootstrap/app.php (Advanced)
```

Select the one that fits your needs. For most cases I recommend going through `Http\Kernel.php`. Use the `--default` option to avoid blocking prompts (could also use the default Laravel command's flag `--no-interaction`).

Both of these methods will replace the default Laravel Router by an extended version provided by this package which contains the Permalink management logic.

**IMPORTANT:** Use either `Http\Kernel.php` or `bootstrap/app.php`. **Do not** use both as it may cause unexpected behaviour.

### Creating a Permalink

That's pretty much it for setting up the dynamic routing system. Let's create a Permalink record and test it out!

```php
Permalink::create([
    'slug' => 'home',
    'action' => 'App\Http\Controllers\HomeController@index'
]);
// Then visit /home
```

### Binding Models to Permalinks

You may want to bind a permalink to a model resource, so you can create a unique URL to access that particular resource. If you want to do so, you just have to use the `HasPermalink` trait and implement the contract `Permalinkable` to your model.

```php
class User extends Model implements \Devio\Permalink\Contracts\Permalinkable;
{
    use \Devio\Permalink\HasPermalinks;
    
    public function permalinkAction()
    {
        return UserController::class . '@show';
    }

    public function permalinkSlug(): array 
    {
        return ['entity.name'];
    }
}
```

The `Permalinkable` interface will force you to define two methods:
#### `permalinkAction()`

This method will return the controller action responsible for handling the request for this particular model. The model itself will be injected into the action (as Laravel usually does for route model binding).

```php 
public function show($user)
{
    return $user;
}
```

#### `permalinkSlug`

This method is a bit more tricky. Since all the slugging task is being handled by the brilliant are using the brilliant [Sluggable](https://github.com/cviebrock/eloquent-sluggable) package, we do have to provide the info this package requires [sluggable](https://github.com/cviebrock/eloquent-sluggable#updating-your-eloquent-models) method.

The permalink model will expose an `entity` polymorphic relationship to this model. Since the slugging occurs in the `Permalink` model class, we do have to specify which is going to be the source for our slug, in this case `entity.name`, so the model itself, attribute `name`. Return multiple items if you would like to concatenate multiple properties:

```
['entity.name', 'entity.city']
```

**NOTE:** This method should return an array compatible with the Sluggable package, please [check the package documentation](https://github.com/cviebrock/eloquent-sluggable#updating-your-eloquent-models) if you want to go deeper.
  
### Creating Permalinks
### Manual creation
### Automatic creation

---

WORK IN PROGRESS...
