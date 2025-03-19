<?php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
Artisan::command('inspire',fn()=>info(Inspiring::quote()))->purpose('Inspire the team');
