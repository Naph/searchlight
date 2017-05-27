## Searchlight
A *Elasticsearch* search query language for **Laravel** featuring multi-model search.
```php
public function search(Search $search, Comment $comments, Post $posts) {
    return $search->in($comments, $posts)->match('Searchlight')->get();
}
```
## Install
Using composer
```bash
composer require naph/searchlight
```
Register the service provider
```php
Naph\Searchlight\SearchlightServiceProvider;
```
Publish vendor files containing driver and host configuration. Lumen users should copy the file instead. 
```bash
php artisan vendor:publish --tag searchlight
```
Setup models by implementing the `SearchlightContract` and `SearchlightTrait`, override `getSearchableFields`
method. The trait binds events to saved and deleted so indices are kept updated with your database.
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
            'title' => 5, 
            'description' => 2.5, 
            'content' => 0.5
        ];
    }
}
```
To resync with an existing database, the models stored in `searchlight.repositories` will have their indices built using the supplied command:
```bash
php artisan index:all
```
## Requirements
Currently only supports PHP7 and latest versions of Laravel and Lumen.
## License
Searchlight is an open-sourced software licensed under the [MIT license](https://raw.githubusercontent.com/Naph/searchlight/master/LICENSE).
