## Searchlight
Simple *Elasticsearch* and *Eloquent* search query language for **Laravel**.
```php
Model::search('Documentation')->get();
```


## Install
Using composer
```bash
composer require naph/searchlight
```
Publish vendor files containing driver and host configuration
```bash
php artisan vendor:publish --tag searchlight
```
Setup models with the both the `SearchlightContract` and `SearchlightTrait` and implement the `getSearchableFields`
method.
```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Naph\Searchlight\SearchlightContract;
use Naph\Searchlight\SearchlightTrait;

class Topic extends Model implements SearchlightContract
{
    use SearchlightTrait;
    
    // ...
    
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
Build the new model's index ...
```bash
php artisan index:all
```
... and search the results!
```php
public function search(Request $request)
{
    $builder = Topic::search($request->input('query'));
}
```
The static search method returns an Eloquent Builder of the search results allowing for deeper filtering.

## Documentation
Documentation for Searchlight can be found in the Wiki

## Requirements
Currently only supports PHP7 and Laravel 5.4.

## License
Searchlight is an open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).
