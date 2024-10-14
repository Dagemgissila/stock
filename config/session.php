<?php
return ['driver'=>env('SESSION_DRIVER','file'),'lifetime'=>env('SESSION_LIFETIME',120),'expire_on_close'=>false,'encrypt'=>false,'files'=>storage_path('framework/sessions'),'cookie'=>env('SESSION_COOKIE','stockmanager_session'),'path'=>'/','http_only'=>true,'same_site'=>'lax'];
