<?php
use Illuminate\Support\Str;
return ['default'=>env('CACHE_DRIVER','redis'),'prefix'=>env('CACHE_PREFIX',Str::slug(env('APP_NAME','laravel'),'_').'_cache_'),'stores'=>['array'=>['driver'=>'array','serialize'=>false],'redis'=>['driver'=>'redis','connection'=>'cache','lock_connection'=>'default'],'file'=>['driver'=>'file','path'=>storage_path('framework/cache/data')],'null'=>['driver'=>'null']]];
