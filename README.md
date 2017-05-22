## Searchlight
Simple *Elasticsearch* and *Eloquent* search query language for **Laravel** and **Lumen**.
```php
$model->search('Documentation')->get();
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
method
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
Updating model entries will index the document. Repositories stored in searchlight config will have their indices re/built using `php artisan index:all`. With indices, you may now use the trait's `search` method
```php
public function search(Request $request, Topic $topic)
{
    $builder = $topic->search($request->input('query'));
}
```
The static search method returns an Eloquent Builder of the search results allowing for deeper filtering. The Eloquent driver, when implemented, will work much the same but the final indexing step can be ignored.
## Requirements
Currently only supports PHP7 and Laravel 5.4.
## License
Searchlight is an open-sourced software licensed under the [MIT license](https://raw.githubusercontent.com/Naph/searchlight/master/LICENSE).
