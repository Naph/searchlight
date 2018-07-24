## Searchlight
An *Elasticsearch* search query language for **Laravel** featuring multi-model search
```php
public function search(Search $search, Comment $comments, Post $posts)
{
    return $search->in($comments, $posts)
        ->match('Searchlight')
        ->get();
}
```
and built-in qualifier reducers.
```php
$driver->qualifier('/#(\w+)/', function (Search $search, $fragment) {
    $search->filter(['tags' => $fragment]);
});
```

## Install
Using composer
```bash
composer require naph/searchlight
```

Register the service provider
```php
Naph\Searchlight\SearchlightServiceProvider::class
```

Publish vendor files containing driver and host configuration. Lumen users should copy the file instead.
```bash
php artisan vendor:publish --tag searchlight
```

Setup models by implementing the contract/trait pair and overriding `getSearchableFields`. The trait binds events to saved and deleted so indices are kept in sync with your database.
```php

use Illuminate\Database\Eloquent\Model;
use Naph\Searchlight\Model\SearchlightContract;
use Naph\Searchlight\Model\SearchlightTrait;

class Topic extends Model implements SearchlightContract
{
    use SearchlightTrait;

    public function getSearchableFields(): array
    {
        return [
            'title' => 1,
            'description' => 0.5,
            'content' => 0.1
        ];
    }
}
```

Storing this model's fully qualified classname in `searchlight.repositories` config ensures their indices are built when running:
```bash
php artisan searchlight:rebuild
```
This command destroys all existing indexed documents in the process.

## Requirements
Currently only supports PHP7 and latest versions of Laravel and Lumen.

## License
Searchlight is an open-sourced software licensed under the [MIT license](https://raw.githubusercontent.com/Naph/searchlight/master/LICENSE).
