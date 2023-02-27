<?php
return [
    'exports' => ['chunk_size' => 1000, 'pre_calculate_formulas' => false,
                  'strict_null_comparison' => false, 'csv' => ['delimiter'=>',','enclosure'=>'"','line_ending'=>"\n"]],
    'imports' => ['read_only' => true, 'ignore_empty' => true, 'heading_row' => ['formatter'=>'slug']],
    'extension_detector' => ['xlsx'=>\PhpOffice\PhpSpreadsheet\Writer\Xlsx::class,'csv'=>\PhpOffice\PhpSpreadsheet\Writer\Csv::class],
    'temporary_files' => ['local_path' => sys_get_temp_dir(), 'remote_disk' => null, 'remote_prefix' => null, 'force_resync_remote' => null],
];
